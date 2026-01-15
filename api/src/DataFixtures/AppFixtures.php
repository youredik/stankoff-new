<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\SupportTicket;
use App\Entity\SupportTicketComment;
use App\Entity\User;
use App\Enum\SupportTicketClosingReason;
use App\Enum\SupportTicketStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

final class AppFixtures extends Fixture
{
    private const array COMMENTS = [
        'Клиент сообщил о проблеме с запуском станка. Станок не включается после нажатия кнопки питания.',
        'Проведена первичная диагностика. Обнаружена неисправность в системе электропитания.',
        'Начинаем работу над заявкой. Специалист выедет на объект для осмотра оборудования.',
        'Проведена проверка электрических цепей. Найдена неисправность в реле управления.',
        'Заменены поврежденные комплектующие. Проводим тестирование работы станка.',
        'Станок запущен в тестовом режиме. Все системы функционируют нормально.',
        'Проведена настройка параметров обработки. Качество работы соответствует требованиям.',
        'Клиент проинформирован о выполненных работах. Станок готов к эксплуатации.',
        'Заявка выполнена успешно. Рекомендуем провести плановое обслуживание через 6 месяцев.',
        'Проблема с подачей материала устранена. Проведена калибровка конвейерной системы.',
        'Обнаружена утечка в гидравлической системе. Произведена замена уплотнительных колец.',
        'Обновлено программное обеспечение ЧПУ. Установлены последние версии драйверов.',
        'Проведена проверка точности позиционирования. Все параметры в допустимых пределах.',
        'Заменены изношенные режущие инструменты. Проводим пробную обработку материала.',
        'Система охлаждения проверена и заправлена. Температурный режим в норме.',
        'Проведена чистка и смазка механических узлов. Улучшена плавность работы.',
        'Настроена система безопасности. Все аварийные датчики функционируют корректно.',
        'Проведено обучение персонала работе с обновленным оборудованием.',
        'Заявка закрыта. Клиент подтвердил устранение всех проблем.',
        'Рекомендуется регулярное техническое обслуживание для предотвращения подобных ситуаций.',
        'Проведена финальная проверка всех систем станка перед сдачей в эксплуатацию.',
        'Документация по выполненным работам передана заказчику.',
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('ru_RU');

        // Create users
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $user = new User();
            $user->email = $faker->unique()->email();
            $user->firstName = $faker->firstName('ru_RU');
            $user->lastName = $faker->lastName('ru_RU');
            $manager->persist($user);
            $users[] = $user;
        }

        // Create support tickets
        $tickets = [];
        for ($i = 0; $i < 10; $i++) {
            $ticket = new SupportTicket();
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
            $ticket->subject = $faker->randomElement($subjects);
            $ticket->description = $faker->randomElement($descriptions);
            $ticket->authorName = $faker->name();
            $ticket->createdAt = \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year', 'now'));

            //$ticket->orderId = $faker->optional(0.7)->numberBetween(1000, 9999);
            $ticket->orderId = 2000;

            /*$ticket->orderData = $faker->optional(0.5)->passthrough([
                'product' => $faker->randomElement(
                    ['Фрезерный станок', 'Токарный станок', 'Пилорама', 'Пресс', 'Сверлильный станок'],
                ),
                'quantity' => $faker->numberBetween(1, 100),
            ]);*/
            $ticket->orderData = [
                'selectedItems' => [
                    '3375_product',
                    '3376_product',
                ],
                'contactName' => $faker->firstName(),
                'contactPhone' => $faker->phoneNumber(),
                'contactEmail' => $faker->email(),
            ];
            $ticket->processInstanceKey = $faker->optional(0.3)->uuid();
            $ticket->user = $faker->randomElement($users);
            $manager->persist($ticket);
            $tickets[] = $ticket;
        }

        // Create comments for each ticket (15-20 comments per ticket)
        foreach ($tickets as $ticket) {
            $numComments = random_int(6, 10);
            $baseDate = $faker->dateTimeBetween('-1 year', 'now');

            // First comment always NEW
            $this->createComment($manager, $faker, $ticket, SupportTicketStatus::NEW, null, $users, $baseDate);

            // Second comment always IN_PROGRESS
            $commentDate = clone $baseDate;
            $commentDate = $commentDate->add(new \DateInterval('PT1H'));
            $this->createComment(
                $manager,
                $faker,
                $ticket,
                SupportTicketStatus::IN_PROGRESS,
                null,
                $users,
                $commentDate,
            );

            // Middle comments with random statuses (excluding COMPLETED)
            $middleStatuses = [SupportTicketStatus::IN_PROGRESS, SupportTicketStatus::POSTPONED];
            for ($i = 2; $i < $numComments - 1; $i++) {
                $status = $faker->randomElement($middleStatuses);
                $commentDate = clone $baseDate;
                $commentDate = $commentDate->add(new \DateInterval('PT' . $i . 'H'));
                $this->createComment($manager, $faker, $ticket, $status, null, $users, $commentDate);
            }

            if (random_int(1, 3) === 1) {
                // Last comment always COMPLETED with closing reason
                $closingReason = $faker->randomElement(SupportTicketClosingReason::cases());
                $commentDate = clone $baseDate;
                $commentDate = $commentDate->add(new \DateInterval('PT' . ($numComments - 1) . 'H'));
                $this->createComment(
                    $manager,
                    $faker,
                    $ticket,
                    SupportTicketStatus::COMPLETED,
                    $closingReason,
                    $users,
                    $commentDate,
                );
            }
        }

        $manager->flush();
    }

    private function createComment(
        ObjectManager $manager,
        $faker,
        SupportTicket $ticket,
        SupportTicketStatus $status,
        ?SupportTicketClosingReason $closingReason,
        array $users,
        \DateTime $date,
    ): void {
        $comment = new SupportTicketComment();
        $comment->comment = $faker->randomElement(self::COMMENTS);
        $comment->status = $status;
        $comment->closingReason = $closingReason;
        $comment->createdAt = \DateTimeImmutable::createFromMutable($date);
        $comment->supportTicket = $ticket;
        $comment->user = $faker->randomElement($users);

        $manager->persist($comment);
    }
}
