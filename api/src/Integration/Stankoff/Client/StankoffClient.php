<?php

declare(strict_types=1);

namespace App\Integration\Stankoff\Client;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Sends a single signed webhook to Stankoff's integration inbox.
 *
 * Responsibilities:
 *   - HTTP send with a strict timeout
 *   - HMAC signature with timestamp generated AT call time (replay window ±5min)
 *   - Translate HTTP outcome into success / Transient / Permanent
 *
 * Does NOT:
 *   - Build payload (PayloadBuilder does)
 *   - Decide retry strategy (Messenger does)
 *   - Persist outbox state (Handler does)
 */
final class StankoffClient
{
    public function __construct(
        #[Autowire(env: 'STANKOFF_BASE_URL')] private readonly string $baseUrl,
        #[Autowire(env: 'STANKOFF_INTEGRATION_ID')] private readonly string $integrationId,
        #[Autowire(env: 'STANKOFF_API_KEY')] private readonly string $apiKey,
        private readonly SignatureFactory $signatureFactory,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Sends the inbox webhook. On success returns the parsed response (eventId/recordId).
     * On failure throws StankoffTransientException or StankoffPermanentException.
     *
     * @param array<string,mixed> $payload Already built by PayloadBuilder
     * @param string $idempotencyKey Stable across retries
     * @return array<string,mixed>
     */
    public function sendInbox(array $payload, string $idempotencyKey): array
    {
        // Stankoff requires 8..128 chars. We always pass UUIDv7 hex (32 chars), but
        // assert defensively so a future bug here surfaces immediately rather than
        // as a 400 on their side.
        $idemLen = strlen($idempotencyKey);
        if ($idemLen < 8 || $idemLen > 128) {
            throw new StankoffPermanentException(
                "Idempotency-Key length {$idemLen} out of range [8,128]",
                errorCode: 'INVALID_IDEMPOTENCY_KEY_LOCAL_GUARD',
            );
        }

        $rawBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        // Hard guard: Stankoff rejects bodies > 1MB with 413. Cheaper to fail fast
        // than make a doomed network call.
        $bodySize = strlen($rawBody);
        if ($bodySize > 1_000_000) {
            throw new StankoffPermanentException(
                "Request body too large for Stankoff inbox: {$bodySize} bytes (limit 1MB)",
                httpStatus: 413,
                errorCode: 'BODY_TOO_LARGE_LOCAL_GUARD',
            );
        }

        $timestamp = SignatureFactory::timestampNow();
        $signature = $this->signatureFactory->sign($timestamp, $rawBody);

        $url = rtrim($this->baseUrl, '/') . '/api/v1/integrations/inbox/' . $this->integrationId;

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Integration-Timestamp' => $timestamp,
                    'X-Integration-Signature' => $signature,
                    'Idempotency-Key' => $idempotencyKey,
                ],
                'body' => $rawBody,
                'timeout' => 30,         // total budget
                'max_duration' => 30,
            ]);

            $status = $response->getStatusCode();
            $responseBody = $response->getContent(throw: false);
        } catch (TransportExceptionInterface $e) {
            // DNS failure, refused connection, timeout — all transient.
            throw new StankoffTransientException(
                "Stankoff network error: {$e->getMessage()}",
                previous: $e,
            );
        } catch (HttpClientException $e) {
            // Anything else from HttpClient (rare) — treat as transient.
            throw new StankoffTransientException(
                "Stankoff HTTP client error: {$e->getMessage()}",
                previous: $e,
            );
        }

        if ($status === 200 || $status === 202) {
            $decoded = self::decodeJsonOrEmpty($responseBody);
            $this->logger->info('stankoff: webhook accepted', [
                'status' => $status,
                'idempotencyKey' => $idempotencyKey,
                'response' => $decoded,
            ]);
            return $decoded;
        }

        $errorCode = self::extractErrorCode($responseBody);
        throw ErrorClassifier::classify($status, $errorCode, $responseBody);
    }

    /**
     * Polls Stankoff's /pull/dedupe endpoint to learn the async-consumer outcome
     * for a previously-accepted webhook. 202-queued only confirms "ingest received";
     * the actual processing happens later — this endpoint surfaces that result.
     *
     * Auth: Bearer (same key as Files API).
     *
     * Returns a normalised structure:
     *   [
     *     'resultStatus'   => 'processed' | 'failed' | 'dlq' | 'pending' | 'unknown',
     *     'resultRecordId' => 'rec_…' | null,
     *     'errorMessage'   => string | null,
     *     'retryCount'     => int,
     *     'raw'            => <full response array, for forensics>,
     *   ]
     *
     * Throws StankoffPermanentException on 4xx (config / wrong key), Transient on 5xx/network.
     * 404 → 'unknown' (TTL expired or never seen — caller decides what that means).
     *
     * @return array<string,mixed>
     */
    public function getDedupeStatus(string $idempotencyKey): array
    {
        // Path per partner-doc 2026-04-29 (commit f5a91a1):
        //   GET /api/v1/integrations/pull/dedupe/{idempotencyKey}
        // integrationId is NOT in path — it's resolved from the Bearer API key.
        $url = sprintf(
            '%s/api/v1/integrations/pull/dedupe/%s',
            rtrim($this->baseUrl, '/'),
            rawurlencode($idempotencyKey),
        );

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
                'max_duration' => 15,
            ]);

            $status = $response->getStatusCode();
            $body = $response->getContent(throw: false);
        } catch (TransportExceptionInterface $e) {
            throw new StankoffTransientException("dedupe-check network error: {$e->getMessage()}", previous: $e);
        } catch (HttpClientException $e) {
            throw new StankoffTransientException("dedupe-check HTTP error: {$e->getMessage()}", previous: $e);
        }

        if ($status === 404) {
            return [
                'resultStatus' => 'unknown',
                'resultRecordId' => null,
                'errorMessage' => null,
                'retryCount' => 0,
                'raw' => null,
            ];
        }

        if ($status >= 500) {
            throw new StankoffTransientException(
                "dedupe-check {$status} (server error)",
                httpStatus: $status,
                responseBody: self::decodeJsonOrEmpty($body) ? json_encode(self::decodeJsonOrEmpty($body)) : null,
            );
        }

        if ($status >= 400) {
            $errorCode = self::extractErrorCode($body);
            throw ErrorClassifier::classify($status, $errorCode, $body);
        }

        $decoded = self::decodeJsonOrEmpty($body);
        // Stankoff doc shows examples both with and without a top-level `data` wrapper —
        // accept both shapes defensively.
        $payload = $decoded['data'] ?? $decoded;

        return [
            'resultStatus' => is_string($payload['resultStatus'] ?? null) ? $payload['resultStatus'] : 'unknown',
            'resultRecordId' => $payload['resultRecordId'] ?? null,
            'errorMessage' => $payload['errorMessage'] ?? null,
            'retryCount' => (int) ($payload['retryCount'] ?? 0),
            'raw' => $decoded,
        ];
    }

    private static function decodeJsonOrEmpty(string $body): array
    {
        if ($body === '') {
            return [];
        }
        try {
            $decoded = json_decode($body, true, 16, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (\JsonException) {
            return [];
        }
    }

    /**
     * Pulls error.code out of the standard Stankoff error envelope:
     *   {"error":{"code":"INVALID_SIGNATURE", ...}}
     */
    private static function extractErrorCode(string $body): ?string
    {
        $decoded = self::decodeJsonOrEmpty($body);
        $code = $decoded['error']['code'] ?? null;
        return is_string($code) ? $code : null;
    }
}
