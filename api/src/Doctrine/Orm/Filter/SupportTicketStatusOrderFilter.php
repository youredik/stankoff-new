<?php

declare(strict_types=1);

namespace App\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\PropertyHelperTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Enum\SupportTicketStatus;
use Doctrine\ORM\QueryBuilder;

final class SupportTicketStatusOrderFilter extends AbstractFilter
{
    use PropertyHelperTrait;

    public function getDescription(string $resourceClass): array
    {
        return [
            'order[status]' => [
                'property' => 'status',
                'type' => 'string',
                'required' => false,
                'description' => 'Custom order for support ticket status.',
            ],
        ];
    }

    public function apply(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if (!isset($context['filters']['order']) || !\is_array($context['filters']['order'])) {
            return;
        }

        $direction = $context['filters']['order']['status'] ?? null;
        $this->filterProperty('status', $direction, $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
    }

    protected function filterProperty(
        string $property,
        mixed $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if ('status' !== $property || !$value) {
            return;
        }

        if (!$this->isPropertyMapped($property, $resourceClass)) {
            return;
        }

        $direction = strtoupper((string) $value);
        if (!\in_array($direction, ['ASC', 'DESC'], true)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $newParam = $queryNameGenerator->generateParameterName('status_new');
        $inProgressParam = $queryNameGenerator->generateParameterName('status_in_progress');
        $postponedParam = $queryNameGenerator->generateParameterName('status_postponed');
        $completedParam = $queryNameGenerator->generateParameterName('status_completed');

        $queryBuilder
            ->addSelect(sprintf(
                'CASE %s.status WHEN :%s THEN 1 WHEN :%s THEN 2 WHEN :%s THEN 3 WHEN :%s THEN 4 ELSE 5 END AS HIDDEN statusOrder',
                $alias,
                $newParam,
                $inProgressParam,
                $postponedParam,
                $completedParam
            ))
            ->addOrderBy('statusOrder', $direction)
            ->setParameter($newParam, SupportTicketStatus::NEW->value)
            ->setParameter($inProgressParam, SupportTicketStatus::IN_PROGRESS->value)
            ->setParameter($postponedParam, SupportTicketStatus::POSTPONED->value)
            ->setParameter($completedParam, SupportTicketStatus::COMPLETED->value);
    }
}
