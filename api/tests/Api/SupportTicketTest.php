<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\DataFixtures\Factory\SupportTicketFactory;
use App\DataFixtures\Factory\UserFactory;
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
}
