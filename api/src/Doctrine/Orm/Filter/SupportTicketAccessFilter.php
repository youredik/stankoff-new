<?php

declare(strict_types=1);

namespace App\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\SupportTicket;
use App\Enum\SupportTicketStatus;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class SupportTicketAccessFilter extends AbstractFilter
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function getDescription(string $resourceClass): array
    {
        return [];
    }

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        // This filter is always applied, no property needed
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (SupportTicket::class !== $resourceClass) {
            return;
        }

        $user = $this->getUser();
        if (!$user instanceof UserInterface) {
            // No user, deny access
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        if ($this->authorizationChecker->isGranted('OIDC_SUPPORT_MANAGER')) {
            // Manager sees all tickets
            return;
        }

        if ($this->authorizationChecker->isGranted('OIDC_SUPPORT_EMPLOYEE')) {
            // Employee sees their own tickets or new tickets
            $userParam = $queryNameGenerator->generateParameterName('user');
            $queryBuilder->leftJoin(\sprintf('%s.user', $alias), 'u');
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    \sprintf('u.id = :%s', $userParam),
                    \sprintf('%s.comments IS EMPTY', $alias) // NEW tickets have no comments
                )
            );
            $queryBuilder->setParameter($userParam, $user->getId());
            return;
        }

        // No role, deny access
        $queryBuilder->andWhere('1 = 0');
    }

    private function getUser(): ?UserInterface
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }
        $user = $token->getUser();
        return $user instanceof UserInterface ? $user : null;
    }
}
