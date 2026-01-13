<?php

declare(strict_types=1);

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

        $statusCode = $response->getStatusCode();

        // Check if response is successful
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \RuntimeException("External API returned HTTP $statusCode");
        }

        $content = $response->getContent();
        $contentType = $response->getHeaders()['content-type'][0] ?? '';

        // Check if response is HTML (error page)
        if (str_contains($contentType, 'text/html') || str_starts_with(trim($content), '<')) {
            throw new \RuntimeException('External API returned HTML instead of JSON');
        }

        // Try to decode JSON
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response from external API');
        }

        return $data;
    }
}
