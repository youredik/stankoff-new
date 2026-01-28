<?php

declare(strict_types=1);

namespace App\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\PropertyHelperTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

final class IdFilter extends AbstractFilter
{
    use PropertyHelperTrait;

    public function getDescription(string $resourceClass): array
    {
        return [
            'id' => [
                'property' => 'id',
                'type' => 'int',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
        ];
    }

    /**
     * @param int|string|null $value
     */
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if ('id' !== $property || !$value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $parameterName = $queryNameGenerator->generateParameterName('id');
        $queryBuilder->setParameter($parameterName, (int) $value);
        $queryBuilder->andWhere($queryBuilder->expr()->eq(\sprintf('%s.id', $alias), ':' . $parameterName));
    }
}
