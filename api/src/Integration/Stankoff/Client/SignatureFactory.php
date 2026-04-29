<?php

declare(strict_types=1);

namespace App\Integration\Stankoff\Client;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Computes the HMAC-SHA256 signature header value for Stankoff webhooks.
 *
 * Spec: signature = "sha256=" + lowercase-hex( HMAC_SHA256( "<timestamp>.<rawBody>", secret ) )
 *
 * The timestamp passed here MUST be byte-identical to the value sent in the
 * X-Integration-Timestamp header — Stankoff hashes the same string.
 *
 * To avoid timing-attack leakage of the secret on signature comparison
 * (relevant only on the verifier side, not us, but still good practice in
 * tests), use hash_equals() when comparing.
 */
final class SignatureFactory
{
    public function __construct(
        #[Autowire(env: 'STANKOFF_HMAC_SECRET')] private readonly string $secret,
    ) {
        if ($this->secret === '') {
            throw new \RuntimeException('STANKOFF_HMAC_SECRET must be set');
        }
    }

    /**
     * @param string $timestamp ISO 8601 UTC with milliseconds, e.g. "2026-04-29T14:30:00.123Z"
     * @param string $rawBody   exact bytes that will be sent on the wire (json_encode result)
     */
    public function sign(string $timestamp, string $rawBody): string
    {
        $hex = hash_hmac('sha256', $timestamp . '.' . $rawBody, $this->secret);
        return 'sha256=' . $hex;
    }

    /**
     * Generates the timestamp header value. Must be called as close as possible
     * to the actual HTTP send to stay within Stankoff's ±5 min replay window.
     */
    public static function timestampNow(): string
    {
        // Y-m-d\TH:i:s.v\Z = "2026-04-29T14:30:00.123Z" (ms precision, UTC)
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->format('Y-m-d\TH:i:s.v\Z');
    }
}
