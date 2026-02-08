<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\DataFixtures\Factory\SupportTicketCommentFactory;
use App\DataFixtures\Factory\SupportTicketFactory;
use App\DataFixtures\Factory\UserFactory;
use App\Enum\SupportTicketStatus;
use App\Repository\SupportTicketRepository;
use App\Tests\Api\Security\TokenGenerator;
use PHPUnit\Framework\Attributes\Test;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class SupportTicketTest extends ApiTestCase
{
    use Factories;
    use ResetDatabase;

    private Client $client;

    protected function setup(): void
    {
        $this->client = self::createClient(['debug' => true]);
    }

    #[Test]
    public function asAdminICanGetACollectionOfSupportTickets(): void
    {
        UserFactory::createMany(5);
        SupportTicketFactory::createMany(10);

        $token = self::getContainer()->get(TokenGenerator::class)->generateToken([
            'email' => UserFactory::createOneAdmin()->email,
        ]);

        $response = $this->client->request('GET', '/support_tickets', ['auth_bearer' => $token]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertJsonContains([
            'hydra:totalItems' => 10,
        ]);
    }

    #[Test]
    public function asAdminICanCreateASupportTicket(): void
    {
        $user = UserFactory::createOne();

        $token = self::getContainer()->get(TokenGenerator::class)->generateToken([
            'email' => UserFactory::createOneAdmin()->email,
        ]);

        $this->client->request('POST', '/support_tickets', [
            'auth_bearer' => $token,
            'json' => [
                'subject' => 'Test ticket',
                'description' => 'Test description',
                'authorName' => 'Test Author',
                'user' => '/users/' . $user->getId(),
                'orderId' => 12345,
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertJsonContains([
            'subject' => 'Test ticket',
            'description' => 'Test description',
            'authorName' => 'Test Author',
            'orderId' => 12345,
        ]);
    }

    #[Test]
    public function asAdminICanGetASupportTicket(): void
    {
        UserFactory::createOne();
        $ticket = SupportTicketFactory::createOne();

        $token = self::getContainer()->get(TokenGenerator::class)->generateToken([
            'email' => UserFactory::createOneAdmin()->email,
        ]);

        $this->client->request('GET', '/support_tickets/' . $ticket->getId(), ['auth_bearer' => $token]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertJsonContains([
            '@id' => '/support_tickets/' . $ticket->getId(),
            'subject' => $ticket->subject,
        ]);
    }

    #[Test]
    public function asNonAdminICannotAccessSupportTickets(): void
    {
        $user = UserFactory::createOne();

        $token = self::getContainer()->get(TokenGenerator::class)->generateToken([
            'email' => $user->email,
        ]);

        $this->client->request('GET', '/support_tickets', ['auth_bearer' => $token]);

        self::assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function asUserICanChangeTicketStatusToInProgress(): void
    {
        $user = UserFactory::createOneAdmin();
        $ticket = SupportTicketFactory::createOne(['user' => $user, 'status' => SupportTicketStatus::NEW]);

        $token = self::getContainer()->get(TokenGenerator::class)->generateToken([
            'email' => $user->email,
            'realm_access' => [
                'roles' => ['ROLE_ADMIN', 'oidc_support_employee'],
            ],
        ]);

        $this->client->request('POST', '/api/support-tickets/' . $ticket->getId() . '/change-status', [
            'auth_bearer' => $token,
            'json' => [
                'status' => 'in_progress',
                'comment' => 'Начинаю работу над заявкой',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'message' => 'Status changed successfully',
        ]);
    }

    #[Test]
    public function asUserICannotChangeTicketStatusToInProgressIfAnotherIsAlreadyInProgress(): void
    {
        $user = UserFactory::createOneAdmin();
        $ticket1 = SupportTicketFactory::createOne(['user' => $user, 'status' => SupportTicketStatus::IN_PROGRESS]);
        $ticket2 = SupportTicketFactory::createOne(['user' => $user]);

        $token = self::getContainer()->get(TokenGenerator::class)->generateToken([
            'email' => $user->email,
            'realm_access' => [
                'roles' => ['admin', 'oidc_support_employee'],
            ],
            'given_name' => $user->firstName,
            'family_name' => $user->lastName,
        ]);

        $this->client->request('POST', '/api/support-tickets/' . $ticket2->getId() . '/change-status', [
            'auth_bearer' => $token,
            'json' => [
                'status' => 'in_progress',
                'comment' => 'Начинаю работу над заявкой',
            ],
        ]);

        self::assertResponseStatusCodeSame(400);
    }

    #[Test]
    public function asAdminICanGetSupportTicketsOrderedByStatus(): void
    {
        UserFactory::createMany(5);
        // Create tickets with different statuses
        $ticket1 = SupportTicketFactory::createOne(['status' => SupportTicketStatus::NEW]);
        SupportTicketCommentFactory::createOne(['supportTicket' => $ticket1, 'status' => SupportTicketStatus::NEW]);

        $ticket2 = SupportTicketFactory::createOne(['status' => SupportTicketStatus::COMPLETED]);
        SupportTicketCommentFactory::createOne(['supportTicket' => $ticket2, 'status' => SupportTicketStatus::COMPLETED]);

        $ticket3 = SupportTicketFactory::createOne(['status' => SupportTicketStatus::IN_PROGRESS]);
        SupportTicketCommentFactory::createOne(['supportTicket' => $ticket3, 'status' => SupportTicketStatus::IN_PROGRESS]);

        $ticket4 = SupportTicketFactory::createOne(['status' => SupportTicketStatus::POSTPONED]);
        SupportTicketCommentFactory::createOne(['supportTicket' => $ticket4, 'status' => SupportTicketStatus::POSTPONED]);

        $token = self::getContainer()->get(TokenGenerator::class)->generateToken([
            'email' => UserFactory::createOneAdmin()->email,
        ]);

        $response = $this->client->request('GET', '/support_tickets?order[status]=asc', ['auth_bearer' => $token]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();

        // Check that the first ticket has the lowest status (new = 1)
        self::assertEquals('new', $data['hydra:member'][0]['currentStatusValue']);
    }

    #[Test]
    public function asSupportManagerCanChangeCompletedTicketStatus(): void
    {
        $user = UserFactory::createOneAdmin();
        $ticket = SupportTicketFactory::createOne(['user' => $user, 'status' => SupportTicketStatus::COMPLETED]);

        $token = self::getContainer()->get(TokenGenerator::class)->generateToken([
            'email' => $user->email,
            'realm_access' => [
                'roles' => ['support_manager', 'oidc_support_manager'],
            ],
        ]);

        $this->client->request('POST', '/api/support-tickets/' . $ticket->getId() . '/change-status', [
            'auth_bearer' => $token,
            'json' => [
                'status' => 'in_progress',
                'comment' => 'Повторно открываем заявку для дополнительной работы',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'message' => 'Status changed successfully',
            'ticket' => [
                'id' => $ticket->getId(),
                'currentStatusValue' => 'in_progress',
            ],
        ]);
    }

    #[Test]
    public function asSupportEmployeeCannotChangeCompletedTicketStatus(): void
    {
        $user = UserFactory::createOne();
        $ticket = SupportTicketFactory::createOne(['user' => $user, 'status' => SupportTicketStatus::COMPLETED]);

        $token = self::getContainer()->get(TokenGenerator::class)->generateToken([
            'email' => $user->email,
            'realm_access' => [
                'roles' => ['support_employee', 'oidc_support_employee'],
            ],
        ]);

        $this->client->request('POST', '/api/support-tickets/' . $ticket->getId() . '/change-status', [
            'auth_bearer' => $token,
            'json' => [
                'status' => 'in_progress',
                'comment' => 'Повторно открываем заявку для дополнительной работы',
            ],
        ]);

        self::assertResponseStatusCodeSame(400);
    }
}
