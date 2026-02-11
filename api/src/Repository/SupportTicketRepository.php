<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SupportTicket;
use App\Entity\User;
use App\Enum\SupportTicketClosingReason;
use App\Enum\SupportTicketStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<SupportTicket>
 *
 * @method SupportTicket|null find($id, $lockMode = null, $lockVersion = null)
 * @method SupportTicket|null findOneBy(array $criteria, array $orderBy = null)
 * @method SupportTicket[]    findAll()
 * @method SupportTicket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SupportTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportTicket::class);
    }

    public function hasUserTicketInProgress(
        UserInterface $user,
        ?int $excludeTicketId = null,
    ): bool {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')           // достаточно посчитать, есть ли хотя бы один
            ->where('t.user = :user')
            ->andWhere('t.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', SupportTicketStatus::IN_PROGRESS)
            ->setMaxResults(1);               // оптимизация — не нужно больше 1

        if ($excludeTicketId !== null) {
            $qb
                ->andWhere('t.id != :excludeId')
                ->setParameter('excludeId', $excludeTicketId);
        }

        return (int)$qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function getCountsByStatusForUser(?User $user, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.status as status, COUNT(t.id) as cnt')
            ->where('t.createdAt >= :from')
            ->andWhere('t.createdAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('t.status');

        if ($user !== null) {
            $qb->andWhere('t.user = :user')->setParameter('user', $user);
        }

        $results = $qb->getQuery()->getResult();

        $counts = [];
        foreach (SupportTicketStatus::cases() as $status) {
            $counts[$status->value] = 0;
        }
        foreach ($results as $row) {
            $counts[$row['status']->value] = (int) $row['cnt'];
        }

        return $counts;
    }

    public function getCompletedCountByUser(?User $user, \DateTimeInterface $from, \DateTimeInterface $to): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->andWhere('t.closedAt >= :from')
            ->andWhere('t.closedAt < :to')
            ->setParameter('status', SupportTicketStatus::COMPLETED)
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        if ($user !== null) {
            $qb->andWhere('t.user = :user')->setParameter('user', $user);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getTotalCountByUser(?User $user, \DateTimeInterface $from, \DateTimeInterface $to): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.createdAt >= :from')
            ->andWhere('t.createdAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        if ($user !== null) {
            $qb->andWhere('t.user = :user')->setParameter('user', $user);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getAverageHandlingTimeMinutes(?User $user, \DateTimeInterface $from, \DateTimeInterface $to): ?float
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT AVG(EXTRACT(EPOCH FROM (closed_at - created_at)) / 60) as avg_minutes
                FROM support_ticket
                WHERE status = :status
                AND closed_at >= :from
                AND closed_at < :to
                AND closed_at IS NOT NULL';
        $params = [
            'status' => SupportTicketStatus::COMPLETED->value,
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
        ];

        if ($user !== null) {
            $sql .= ' AND user_id = :userId';
            $params['userId'] = $user->getId();
        }

        $result = $conn->fetchOne($sql, $params);

        return $result !== false && $result !== null ? round((float) $result, 1) : null;
    }

    public function getOverdueCount(?User $user, \DateTimeInterface $from, \DateTimeInterface $to, int $maxMinutes): int
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT COUNT(*) FROM support_ticket
                WHERE status = :status
                AND closed_at >= :from
                AND closed_at < :to
                AND closed_at IS NOT NULL
                AND EXTRACT(EPOCH FROM (closed_at - created_at)) / 60 > :maxMinutes';
        $params = [
            'status' => SupportTicketStatus::COMPLETED->value,
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
            'maxMinutes' => $maxMinutes,
        ];

        if ($user !== null) {
            $sql .= ' AND user_id = :userId';
            $params['userId'] = $user->getId();
        }

        return (int) $conn->fetchOne($sql, $params);
    }

    /**
     * @return array<int, array{id: int, subject: string, author_name: string, contractor: ?string, order_id: ?int, user_name: ?string, created_at: string, closed_at: string, handling_time_minutes: float, closing_comment: ?string}>
     */
    public function findCompletedResolvedInPeriod(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT
                t.id,
                t.subject,
                t.author_name,
                t.contractor,
                t.order_id,
                u.first_name || ' ' || u.last_name AS user_name,
                t.created_at,
                t.closed_at,
                EXTRACT(EPOCH FROM (t.closed_at - t.created_at)) / 60 AS handling_time_minutes,
                c.comment AS closing_comment
            FROM support_ticket t
            LEFT JOIN "user" u ON u.id = t.user_id
            INNER JOIN support_ticket_comment c ON c.support_ticket_id = t.id
                AND c.closing_reason = :closingReason
                AND c.id = (
                    SELECT MAX(c2.id)
                    FROM support_ticket_comment c2
                    WHERE c2.support_ticket_id = t.id
                      AND c2.status = :status
                )
            WHERE t.status = :status
              AND t.closed_at >= :from
              AND t.closed_at < :to
            ORDER BY t.closed_at DESC
        SQL;

        return $conn->fetchAllAssociative($sql, [
            'status' => SupportTicketStatus::COMPLETED->value,
            'closingReason' => SupportTicketClosingReason::RESOLVED->value,
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
        ]);
    }
}
