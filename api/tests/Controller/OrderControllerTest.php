<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Repository\OldSiteApiRepository;

final class OrderControllerTest extends ApiTestCase
{
    private Client $client;

    protected function setup(): void
    {
        $this->client = self::createClient(['debug' => true]);
    }

    public function testGetOrderReturnsJsonResponse(): void
    {
        // Mock the OldSiteApiRepository
        $mockRepository = $this->createMock(OldSiteApiRepository::class);
        $mockRepository->method('getOrder')
            ->willReturn([
                'id' => 12345,
                'number' => 'ORD-12345',
                'counterparty_name' => 'Test Company',
                'counterparty_inn' => '1234567890',
                'counterparty_kpp' => '123456789',
                'manager' => 'Test Manager',
                'items' => [['name' => 'Test Item', 'quantity' => 1]],
            ]);

        self::getContainer()->set(OldSiteApiRepository::class, $mockRepository);

        $this->client->request('GET', '/api/orders/12345');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonContains([
            'order' => [
                'id' => 12345,
                'number' => 'ORD-12345',
            ],
        ]);
    }
}
