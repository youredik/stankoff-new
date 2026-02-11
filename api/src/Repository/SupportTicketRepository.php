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

    public function findTicketsWithAcceptanceTime(\DateTimeInterface $from, \DateTimeInterface $to, ?int $userId = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT
                t.id,
                t.subject,
                t.contractor,
                u.first_name || ' ' || u.last_name AS user_name,
                EXTRACT(EPOCH FROM (t.accepted_at - t.created_at)) / 60 AS acceptance_time_minutes
            FROM support_ticket t
            LEFT JOIN "user" u ON u.id = t.user_id
            WHERE t.accepted_at IS NOT NULL
              AND t.status IN ('in_progress', 'postponed', 'completed')
              AND t.accepted_at >= :from
              AND t.accepted_at < :to
        SQL;

        $params = [
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
        ];

        if ($userId !== null) {
            $sql .= ' AND t.user_id = :userId';
            $params['userId'] = $userId;
        }

        $sql .= ' ORDER BY t.accepted_at DESC';

        return $conn->fetchAllAssociative($sql, $params);
    }

    public function findTicketsWithResolutionTime(\DateTimeInterface $from, \DateTimeInterface $to, ?int $userId = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT
                t.id,
                t.subject,
                t.contractor,
                u.first_name || ' ' || u.last_name AS user_name,
                EXTRACT(EPOCH FROM (t.closed_at - t.accepted_at)) / 60 AS resolution_time_minutes
            FROM support_ticket t
            LEFT JOIN "user" u ON u.id = t.user_id
            WHERE t.status = :status
              AND t.closed_at IS NOT NULL
              AND t.accepted_at IS NOT NULL
              AND t.closed_at >= :from
              AND t.closed_at < :to
        SQL;

        $params = [
            'status' => SupportTicketStatus::COMPLETED->value,
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
        ];

        if ($userId !== null) {
            $sql .= ' AND t.user_id = :userId';
            $params['userId'] = $userId;
        }

        $sql .= ' ORDER BY t.closed_at DESC';

        return $conn->fetchAllAssociative($sql, $params);
    }

    public function findCompletedWithClosingReasons(\DateTimeInterface $from, \DateTimeInterface $to, ?int $userId = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT
                t.id,
                t.subject,
                t.contractor,
                u.first_name || ' ' || u.last_name AS user_name,
                c.closing_reason
            FROM support_ticket t
            LEFT JOIN "user" u ON u.id = t.user_id
            INNER JOIN support_ticket_comment c ON c.support_ticket_id = t.id
                AND c.id = (
                    SELECT MAX(c2.id)
                    FROM support_ticket_comment c2
                    WHERE c2.support_ticket_id = t.id
                      AND c2.status = :status
                      AND c2.closing_reason IS NOT NULL
                )
            WHERE t.status = :status
              AND t.closed_at >= :from
              AND t.closed_at < :to
        SQL;

        $params = [
            'status' => SupportTicketStatus::COMPLETED->value,
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
        ];

        if ($userId !== null) {
            $sql .= ' AND t.user_id = :userId';
            $params['userId'] = $userId;
        }

        $sql .= ' ORDER BY t.closed_at DESC';

        return $conn->fetchAllAssociative($sql, $params);
    }

    public function getClosingReasonCounts(\DateTimeInterface $from, \DateTimeInterface $to, ?int $userId = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT
                c.closing_reason,
                COUNT(*) AS cnt
            FROM support_ticket t
            INNER JOIN support_ticket_comment c ON c.support_ticket_id = t.id
                AND c.id = (
                    SELECT MAX(c2.id)
                    FROM support_ticket_comment c2
                    WHERE c2.support_ticket_id = t.id
                      AND c2.status = :status
                      AND c2.closing_reason IS NOT NULL
                )
            WHERE t.status = :status
              AND t.closed_at >= :from
              AND t.closed_at < :to
        SQL;

        $params = [
            'status' => SupportTicketStatus::COMPLETED->value,
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
        ];

        if ($userId !== null) {
            $sql .= ' AND t.user_id = :userId';
            $params['userId'] = $userId;
        }

        $sql .= ' GROUP BY c.closing_reason';

        $rows = $conn->fetchAllAssociative($sql, $params);

        $counts = [];
        foreach (SupportTicketClosingReason::cases() as $reason) {
            $counts[$reason->value] = 0;
        }
        foreach ($rows as $row) {
            if ($row['closing_reason'] !== null) {
                $counts[$row['closing_reason']] = (int) $row['cnt'];
            }
        }

        return $counts;
    }

    public function getHourlyDistribution(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT
                EXTRACT(HOUR FROM created_at) AS hour,
                COUNT(*) AS cnt
            FROM support_ticket
            WHERE created_at >= :from
              AND created_at < :to
            GROUP BY EXTRACT(HOUR FROM created_at)
            ORDER BY hour
        SQL;

        $rows = $conn->fetchAllAssociative($sql, [
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
        ]);

        $result = array_fill(0, 24, 0);
        foreach ($rows as $row) {
            $result[(int) $row['hour']] = (int) $row['cnt'];
        }

        return $result;
    }

    public function getHourlyActivity(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT
                EXTRACT(HOUR FROM created_at) AS hour,
                COUNT(*) AS cnt
            FROM support_ticket_comment
            WHERE created_at >= :from
              AND created_at < :to
            GROUP BY EXTRACT(HOUR FROM created_at)
            ORDER BY hour
        SQL;

        $rows = $conn->fetchAllAssociative($sql, [
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
        ]);

        $result = array_fill(0, 24, 0);
        foreach ($rows as $row) {
            $result[(int) $row['hour']] = (int) $row['cnt'];
        }

        return $result;
    }

    public function getEmployeeSummaryForPeriod(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT
                u.id AS user_id,
                u.first_name || ' ' || u.last_name AS user_name,
                COUNT(CASE WHEN t.status = 'completed' AND t.closed_at >= :from AND t.closed_at < :to THEN 1 END) AS completed_count,
                AVG(CASE WHEN t.accepted_at IS NOT NULL AND t.status IN ('in_progress', 'postponed', 'completed') AND t.accepted_at >= :from AND t.accepted_at < :to
                    THEN EXTRACT(EPOCH FROM (t.accepted_at - t.created_at)) / 60 END) AS avg_acceptance_minutes,
                AVG(CASE WHEN t.status = 'completed' AND t.closed_at >= :from AND t.closed_at < :to AND t.accepted_at IS NOT NULL
                    THEN EXTRACT(EPOCH FROM (t.closed_at - t.accepted_at)) / 60 END) AS avg_resolution_minutes,
                COUNT(CASE WHEN t.accepted_at IS NOT NULL AND t.accepted_at >= :from AND t.accepted_at < :to
                    AND EXTRACT(EPOCH FROM (t.accepted_at - t.created_at)) / 60 > :maxAcceptance THEN 1 END) AS acceptance_overdue_count,
                COUNT(CASE WHEN t.status = 'completed' AND t.closed_at >= :from AND t.closed_at < :to AND t.accepted_at IS NOT NULL
                    AND EXTRACT(EPOCH FROM (t.closed_at - t.accepted_at)) / 60 > :maxResolution THEN 1 END) AS resolution_overdue_count
            FROM "user" u
            LEFT JOIN support_ticket t ON t.user_id = u.id
            GROUP BY u.id, u.first_name, u.last_name
            HAVING COUNT(t.id) > 0
            ORDER BY user_name
        SQL;

        return $conn->fetchAllAssociative($sql, [
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
            'maxAcceptance' => 120,
            'maxResolution' => 2880,
        ]);
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
