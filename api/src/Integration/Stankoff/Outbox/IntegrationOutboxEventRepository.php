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
     * never polled OR last-polled status was 'pending' (Stankoff's consumer
     * still working). Both have a min-age guard to give the remote consumer
     * time to do its work — there's no point polling a row 1 sec after dispatch.
     *
     * @return list<IntegrationOutboxEvent>
     */
    public function findSucceededNeedingDedupeCheck(int $minAgeSec, int $limit): array
    {
        $cutoff = new \DateTimeImmutable("-{$minAgeSec} seconds");

        // Re-poll if:
        //   - never polled, OR
        //   - last remote status was non-terminal ('pending' = consumer still working,
        //     'unknown' = 404 likely mapping bug per partner-doc), AND last poll was
        //     longer ago than the min-age — so we don't hammer.
        // 'processed', 'failed', 'dlq' are terminal; row stops being returned.
        $qb = $this->createQueryBuilder('o')
            ->where('o.status = :st')
            ->andWhere('o.succeededAt < :cutoff')
            ->andWhere('o.lastDedupeCheckAt IS NULL OR (o.dedupeRemoteStatus IN (:nonTerminal) AND o.lastDedupeCheckAt < :cutoff)')
            ->setParameter('st', OutboxStatus::SUCCEEDED)
            ->setParameter('nonTerminal', ['pending', 'unknown'])
            ->setParameter('cutoff', $cutoff)
            ->orderBy('o.succeededAt', 'ASC')
            ->setMaxResults($limit);

        /** @var list<IntegrationOutboxEvent> */
        return $qb->getQuery()->getResult();
    }
}
