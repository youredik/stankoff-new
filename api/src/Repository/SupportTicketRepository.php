<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SupportTicket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    public function hasUserTicketInProgress(\Symfony\Component\Security\Core\User\UserInterface $user, ?int $excludeTicketId = null): bool
    {
        $tickets = $this->findBy(['user' => $user]);
        $inProgressCount = 0;

        foreach ($tickets as $ticket) {
            if ($excludeTicketId !== null && $ticket->getId() === $excludeTicketId) {
                continue;
            }
            if ($ticket->getCurrentStatusValue() === \App\Enum\SupportTicketStatus::IN_PROGRESS->value) {
                $inProgressCount++;
                if ($inProgressCount >= 3) {
                    return true;
                }
            }
        }

        return false;
    }
//
//    public function save(SupportTicket $entity, bool $flush = false): void
//    {
//        $this->getEntityManager()->persist($entity);
//
//        if ($flush) {
//            $this->getEntityManager()->flush();
//        }
//    }
//
//    public function remove(SupportTicket $entity, bool $flush = false): void
//    {
//        $this->getEntityManager()->remove($entity);
//
//        if ($flush) {
//            $this->getEntityManager()->flush();
//        }
//    }
}
