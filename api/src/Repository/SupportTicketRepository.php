<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SupportTicket;
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

    /*public function hasUserTicketInProgress(
        UserInterface $user,
        ?int $excludeTicketId = null,
    ): bool {
        $tickets = $this->findBy(['user' => $user]);

        foreach ($tickets as $ticket) {
            if ($ticket->getId() === $excludeTicketId) {
                continue;
            }
            if ($ticket->status === SupportTicketStatus::IN_PROGRESS) {
                return true;
            }
        }

        return false;
    }*/
}
