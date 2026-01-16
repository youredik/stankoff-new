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
        $user = UserFactory::createOne();
        $ticket = SupportTicketFactory::createOne(['user' => $user]);

        $token = self::getContainer()->get(TokenGenerator::class)->generateToken([
            'email' => $user->email,
        ]);

        $this->client->request('PATCH', '/support_tickets/' . $ticket->getId() . '/change_status', [
            'auth_bearer' => $token,
            'json' => [
                'status' => 'IN_PROGRESS',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'status' => 'IN_PROGRESS',
        ]);
    }

    #[Test]
    public function asUserICannotChangeTicketStatusToInProgressIfAnotherIsAlreadyInProgress(): void
    {
        $user = UserFactory::createOne();
        $ticket1 = SupportTicketFactory::createOne(['user' => $user, 'status' => 'IN_PROGRESS']);
        $ticket2 = SupportTicketFactory::createOne(['user' => $user]);

        $token = self::getContainer()->get(TokenGenerator::class)->generateToken([
            'email' => $user->email,
        ]);

        $this->client->request('PATCH', '/support_tickets/' . $ticket2->getId() . '/change_status', [
            'auth_bearer' => $token,
            'json' => [
                'status' => 'IN_PROGRESS',
            ],
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertJsonContains([
            'detail' => 'У вас уже имеется заявка в работе. Отложите действующую заявку чтобы взять новую.',
        ]);
    }

    #[Test]
    public function asAdminICanGetSupportTicketsOrderedByStatus(): void
    {
        UserFactory::createMany(5);
        // Create tickets with different statuses via comments
        $ticket1 = SupportTicketFactory::createOne();
        SupportTicketCommentFactory::createOne(['supportTicket' => $ticket1, 'status' => SupportTicketStatus::NEW]);

        $ticket2 = SupportTicketFactory::createOne();
        SupportTicketCommentFactory::createOne(['supportTicket' => $ticket2, 'status' => SupportTicketStatus::COMPLETED]);

        $ticket3 = SupportTicketFactory::createOne();
        SupportTicketCommentFactory::createOne(['supportTicket' => $ticket3, 'status' => SupportTicketStatus::IN_PROGRESS]);

        $ticket4 = SupportTicketFactory::createOne();
        SupportTicketCommentFactory::createOne(['supportTicket' => $ticket4, 'status' => SupportTicketStatus::POSTPONED]);

        $token = self::getContainer()->get(TokenGenerator::class)->generateToken([
            'email' => UserFactory::createOneAdmin()->email,
        ]);

        $response = $this->client->request('GET', '/support_tickets?order[currentStatusValue]=asc', ['auth_bearer' => $token]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();

        // Check that the first ticket has the lowest status (new = 1)
        self::assertEquals('new', $data['hydra:member'][0]['currentStatusValue']);
    }
}
