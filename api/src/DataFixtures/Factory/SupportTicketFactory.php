<?php

declare(strict_types=1);

namespace App\DataFixtures\Factory;

use App\Entity\SupportTicket;
use App\Repository\SupportTicketRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @method        SupportTicket|Proxy                                     create(array|callable $attributes = [])
 * @method static SupportTicket|Proxy                                     createOne(array $attributes = [])
 * @method static SupportTicket|Proxy                                     find(object|array|mixed $criteria)
 * @method static SupportTicket|Proxy                                     findOrCreate(array $attributes)
 * @method static SupportTicket|Proxy                                     first(string $sortedField = 'id')
 * @method static SupportTicket|Proxy                                     last(string $sortedField = 'id')
 * @method static SupportTicket|Proxy                                     random(array $attributes = [])
 * @method static SupportTicket|Proxy                                     randomOrCreate(array $attributes = [])
 * @method static SupportTicket[]|Proxy[]                                 all()
 * @method static SupportTicket[]|Proxy[]                                 createMany(int $number, array|callable $attributes = [])
 * @method static SupportTicket[]|Proxy[]                                 createSequence(iterable|callable $sequence)
 * @method static SupportTicket[]|Proxy[]                                 findBy(array $attributes)
 * @method static SupportTicket[]|Proxy[]                                 randomRange(int $min, int $max, array $attributes = [])
 * @method static SupportTicket[]|Proxy[]                                 randomSet(int $number, array $attributes = [])
 * @method        FactoryCollection<SupportTicket|Proxy>                  many(int $min, int|null $max = null)
 * @method        FactoryCollection<SupportTicket|Proxy>                  sequence(iterable|callable $sequence)
 * @method static ProxyRepositoryDecorator<SupportTicket, SupportTicketRepository> repository()
 *
 * @phpstan-method SupportTicket&Proxy<SupportTicket> create(array|callable $attributes = [])
 * @phpstan-method static SupportTicket&Proxy<SupportTicket> createOne(array $attributes = [])
 * @phpstan-method static SupportTicket&Proxy<SupportTicket> find(object|array|mixed $criteria)
 * @phpstan-method static SupportTicket&Proxy<SupportTicket> findOrCreate(array $attributes)
 * @phpstan-method static SupportTicket&Proxy<SupportTicket> first(string $sortedField = 'id')
 * @phpstan-method static SupportTicket&Proxy<SupportTicket> last(string $sortedField = 'id')
 * @phpstan-method static SupportTicket&Proxy<SupportTicket> random(array $attributes = [])
 * @phpstan-method static SupportTicket&Proxy<SupportTicket> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<SupportTicket&Proxy<SupportTicket>> all()
 * @phpstan-method static list<SupportTicket&Proxy<SupportTicket>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<SupportTicket&Proxy<SupportTicket>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<SupportTicket&Proxy<SupportTicket>> findBy(array $attributes)
 * @phpstan-method static list<SupportTicket&Proxy<SupportTicket>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<SupportTicket&Proxy<SupportTicket>> randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<SupportTicket&Proxy<SupportTicket>> many(int $min, int|null $max = null)
 * @method FactoryCollection<SupportTicket&Proxy<SupportTicket>> sequence(iterable|callable $sequence)
 *
 * @extends PersistentProxyObjectFactory<SupportTicket>
 */
final class SupportTicketFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array
    {
        $subjects = [
            'Проблема с запуском деревообрабатывающего станка',
            'Неисправность в системе ЧПУ металлообрабатывающего станка',
            'Требуется настройка программного обеспечения для станка',
            'Проблема с подачей материала на деревообрабатывающий станок',
            'Не работает система охлаждения на металлообрабатывающем станке',
            'Требуется замена расходных материалов для станка',
            'Ошибка в работе конвейерной системы подачи',
            'Проблема с точностью обработки на фрезерном станке',
            'Неисправность гидравлической системы пресса',
            'Требуется калибровка измерительных приборов станка',
        ];

        $descriptions = [
            'Клиент сообщает о проблеме с запуском станка после последнего обслуживания. Станок не реагирует на команды управления.',
            'Во время работы станка произошел сбой в системе управления. Необходимо провести диагностику и ремонт.',
            'Требуется настройка параметров обработки для нового типа материала. Станок работает, но качество обработки неудовлетворительное.',
            'После замены комплектующих станок работает нестабильно. Необходима проверка всех систем и настройка.',
            'Клиент жалуется на посторонние шумы во время работы станка. Требуется осмотр и устранение причины.',
            'Станок остановился во время работы с ошибкой в системе безопасности. Необходима проверка датчиков и реле.',
            'Требуется обновление программного обеспечения для улучшения производительности и добавления новых функций.',
            'После транспортировки станок требует повторной установки и настройки на новом месте.',
        ];

        return [
            'subject' => self::faker()->randomElement($subjects),
            'description' => self::faker()->randomElement($descriptions),
            'createdAt' => self::faker()->dateTimeBetween('-1 year', 'now'),
            'orderId' => self::faker()->optional(0.7)->numberBetween(1000, 9999),
            'orderData' => self::faker()->optional(0.5)->passthrough([
                'product' => self::faker()->randomElement(['Фрезерный станок', 'Токарный станок', 'Пилорама', 'Пресс', 'Сверлильный станок']),
                'quantity' => self::faker()->numberBetween(1, 100),
            ]),
            'processInstanceKey' => self::faker()->optional(0.3)->uuid(),
            'user' => UserFactory::random(),
        ];
    }

    public static function class(): string
    {
        return SupportTicket::class;
    }
}
