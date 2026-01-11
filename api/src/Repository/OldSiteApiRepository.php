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
        $this->baseUrl = 'https://www.stankoff.ru';
        //$this->token = $_ENV['OLD_SITE_API_TOKEN'];
        $this->token = 'snchZ2V0X2FwaV90b2tlbl90b19jaGFuZ2VNaWUK';
    }

    private string $token;

    public function getOrder(int $orderId): array
    {
        $response = $this->client->request(
            'GET',
            $this->baseUrl . '/actions/ajax/order_data.php?order_id=' . $orderId,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Cookie' => 'PHPSESSID=494b2e20939317cbbab1901412521e54; __st_id=%2BsbQ.Nik_QGRFNr_QONWcNzBCk0rPaxF3ltiKqdFpERUgQ6-f2',
                ],
            ],
        );

        return $response->toArray();
    }
}
