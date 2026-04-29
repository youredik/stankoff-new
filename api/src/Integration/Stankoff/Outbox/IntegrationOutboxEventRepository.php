<?php

declare(strict_types=1);

namespace App\Integration\Stankoff\Outbox;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<IntegrationOutboxEvent>
 */
class IntegrationOutboxEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IntegrationOutboxEvent::class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?IntegrationOutboxEvent
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function findByIdString(string $id): ?IntegrationOutboxEvent
    {
        return $this->find(Uuid::fromString($id));
    }

    /**
     * Rows the dedupe-check cron should poll: locally succeeded, but either
     * never polled OR last-polled status was non-terminal — Stankoff's consumer
     * is still working ('deferred') or we got a 404 ('unknown'). Both have a
     * min-age guard to give the remote consumer time to do its work — there's
     * no point polling a row 1 sec after dispatch.
     *
     * @return list<IntegrationOutboxEvent>
     */
    public function findSucceededNeedingDedupeCheck(int $minAgeSec, int $limit): array
    {
        $cutoff = new \DateTimeImmutable("-{$minAgeSec} seconds");

        // Re-poll if:
        //   - never polled, OR
        //   - last remote status was non-terminal ('deferred' = initial state, set
        //     by Stankoff's webhook receiver inside the persist transaction; their
        //     async consumer transitions it to a terminal state. 'unknown' = our
        //     local mapping for 404, kept polling until TTL=7d), AND last poll was
        //     longer ago than the min-age — so we don't hammer.
        // 'processed', 'failed', 'dlq' are terminal; row stops being returned.
        // Worst-case 'deferred' duration per partner 2026-04-29: ~25 min (5x
        // 5-min consumer leases on parent-ref redelivery). After 30 min it's a
        // bug on their side — surface via lift to permanently_failed eventually.
        $qb = $this->createQueryBuilder('o')
            ->where('o.status = :st')
            ->andWhere('o.succeededAt < :cutoff')
            ->andWhere('o.lastDedupeCheckAt IS NULL OR (o.dedupeRemoteStatus IN (:nonTerminal) AND o.lastDedupeCheckAt < :cutoff)')
            ->setParameter('st', OutboxStatus::SUCCEEDED)
            ->setParameter('nonTerminal', ['deferred', 'unknown'])
            ->setParameter('cutoff', $cutoff)
            ->orderBy('o.succeededAt', 'ASC')
            ->setMaxResults($limit);

        /** @var list<IntegrationOutboxEvent> */
        return $qb->getQuery()->getResult();
    }
}
