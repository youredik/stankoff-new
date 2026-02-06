<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GuestAccessToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GuestAccessToken>
 */
final class GuestAccessTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GuestAccessToken::class);
    }

    public function findValidByToken(string $token): ?GuestAccessToken
    {
        $hash = GuestAccessToken::hashToken($token);
        $entity = $this->findOneBy(['tokenHash' => $hash]);
        if (!$entity) {
            return null;
        }

        if ($entity->isRevoked() || $entity->isExpired()) {
            return null;
        }

        return $entity;
    }
}
