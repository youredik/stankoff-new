<?php

declare(strict_types=1);

namespace App\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\PropertyHelperTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

final class NameFilter extends AbstractFilter
{
    use PropertyHelperTrait;

    public function getDescription(string $resourceClass): array
    {
        return [
            'user' => [
                'property' => 'user',
                'type' => 'string',
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ],
        ];
    }

    /**
     * @param string|null $value
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
        if ('user' !== $property || !$value) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $parameterName = $queryNameGenerator->generateParameterName('user');
        $queryBuilder->setParameter($parameterName, (int) $value);
        $queryBuilder->andWhere($queryBuilder->expr()->eq(\sprintf('%s.user', $alias), ':' . $parameterName));
    }

    private function normalizeValues(?string $value, string $property): ?array
    {
        if (!\is_string($value) || empty(trim($value))) {
            return null;
        }

        $parts = explode(' ', $value);
        foreach ($parts as $key => $part) {
            if (empty(trim($part))) {
                unset($parts[$key]);
            }
        }
        $values = array_values($parts);

        if (empty($values)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new \InvalidArgumentException(
                    \sprintf(
                        'At least one value is required, multiple values should be in "%1$s[]=firstvalue&%1$s[]=secondvalue" format',
                        $property,
                    ),
                ),
            ]);

            return null;
        }

        return $values;
    }
}
