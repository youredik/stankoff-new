<?php

declare(strict_types=1);

namespace App\DataFixtures\Factory;

use App\Entity\SupportTicketComment;
use App\Enum\SupportTicketClosingReason;
use App\Enum\SupportTicketStatus;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;

/**
 * @method        SupportTicketComment|Proxy                                     create(array|callable $attributes = [])
 * @method static SupportTicketComment|Proxy                                     createOne(array $attributes = [])
 * @method static SupportTicketComment|Proxy                                     find(object|array|mixed $criteria)
 * @method static SupportTicketComment|Proxy                                     findOrCreate(array $attributes)
 * @method static SupportTicketComment|Proxy                                     first(string $sortedField = 'id')
 * @method static SupportTicketComment|Proxy                                     last(string $sortedField = 'id')
 * @method static SupportTicketComment|Proxy                                     random(array $attributes = [])
 * @method static SupportTicketComment|Proxy                                     randomOrCreate(array $attributes = [])
 * @method static SupportTicketComment[]|Proxy[]                                 all()
 * @method static SupportTicketComment[]|Proxy[]                                 createMany(int $number, array|callable $attributes = [])
 * @method static SupportTicketComment[]|Proxy[]                                 createSequence(iterable|callable $sequence)
 * @method static SupportTicketComment[]|Proxy[]                                 findBy(array $attributes)
 * @method static SupportTicketComment[]|Proxy[]                                 randomRange(int $min, int $max, array $attributes = [])
 * @method static SupportTicketComment[]|Proxy[]                                 randomSet(int $number, array $attributes = [])
 * @method        FactoryCollection<SupportTicketComment|Proxy>                  many(int $min, int|null $max = null)
 * @method        FactoryCollection<SupportTicketComment|Proxy>                  sequence(iterable|callable $sequence)
 *
 * @phpstan-method SupportTicketComment&Proxy<SupportTicketComment> create(array|callable $attributes = [])
 * @phpstan-method static SupportTicketComment&Proxy<SupportTicketComment> createOne(array $attributes = [])
 * @phpstan-method static SupportTicketComment&Proxy<SupportTicketComment> find(object|array|mixed $criteria)
 * @phpstan-method static SupportTicketComment&Proxy<SupportTicketComment> findOrCreate(array $attributes)
 * @phpstan-method static SupportTicketComment&Proxy<SupportTicketComment> first(string $sortedField = 'id')
 * @phpstan-method static SupportTicketComment&Proxy<SupportTicketComment> last(string $sortedField = 'id')
 * @phpstan-method static SupportTicketComment&Proxy<SupportTicketComment> random(array $attributes = [])
 * @phpstan-method static SupportTicketComment&Proxy<SupportTicketComment> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<SupportTicketComment&Proxy<SupportTicketComment>> all()
 * @phpstan-method static list<SupportTicketComment&Proxy<SupportTicketComment>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<SupportTicketComment&Proxy<SupportTicketComment>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<SupportTicketComment&Proxy<SupportTicketComment>> findBy(array $attributes)
 * @phpstan-method static list<SupportTicketComment&Proxy<SupportTicketComment>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<SupportTicketComment&Proxy<SupportTicketComment>> randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<SupportTicketComment&Proxy<SupportTicketComment>> many(int $min, int|null $max = null)
 * @method FactoryCollection<SupportTicketComment&Proxy<SupportTicketComment>> sequence(iterable|callable $sequence)
 *
 * @extends PersistentProxyObjectFactory<SupportTicketComment>
 */
final class SupportTicketCommentFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array
    {
        $status = self::faker()->randomElement(SupportTicketStatus::cases());

        return [
            'comment' => self::faker()->paragraph(2, 'ru_RU'),
            'closingReason' => $status === SupportTicketStatus::COMPLETED
                ? self::faker()->randomElement(SupportTicketClosingReason::cases())
                : null,
            'status' => $status,
            'createdAt' => self::faker()->dateTimeBetween('-1 year', 'now'),
            'supportTicket' => SupportTicketFactory::random(),
            'user' => UserFactory::random(),
        ];
    }

    public static function class(): string
    {
        return SupportTicketComment::class;
    }
}
