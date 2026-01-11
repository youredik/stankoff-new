<?php

namespace App\Repository;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OldSiteApiRepository
{
    private HttpClientInterface $client;
    private string $baseUrl;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->baseUrl = 'http://lamp-php83';
        $this->token = $_ENV['OLD_SITE_API_TOKEN'];
    }

    private string $token;

    public function getOrder(int $orderId): array
    {
        $response = $this->client->request('GET', $this->baseUrl . '/actions/ajax/order_data.php?order_id=' . $orderId, [
            'max_redirects' => 0,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
            ],
        ]);
        return $response->toArray();
    }
}
