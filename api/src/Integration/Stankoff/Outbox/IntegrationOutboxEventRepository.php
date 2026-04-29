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
}
