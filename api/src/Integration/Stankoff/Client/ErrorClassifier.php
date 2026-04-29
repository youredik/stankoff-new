<?php

declare(strict_types=1);

namespace App\Integration\Stankoff\Client;

/**
 * Translates an HTTP response from Stankoff (status + parsed error body) into
 * the right exception class. Hard-codes the contract from the partner doc:
 *
 *  RETRY (transient):
 *    - any 5xx
 *    - 408 Request Timeout (rare, but per RFC retryable)
 *    - 429 Too Many Requests (rate-limit; expected when Stankoff ships their
 *      Phase 2 throttling — must NOT be permanent)
 *    - 401 with errorCode == INVALID_TIMESTAMP | REPLAY_WINDOW_EXCEEDED
 *      (clock drift; next attempt regenerates timestamp)
 *
 *  PERMANENT:
 *    - 400 (any errorCode — payload bug)
 *    - 401 with errorCode == INVALID_SIGNATURE | INVALID_SIGNATURE_FORMAT | MISSING_HEADERS
 *    - 404 (unknown integration id — config error)
 *    - 413 (body too large)
 *    - any other 4xx we haven't seen — default to permanent (safer than infinite retry)
 *
 * Network errors and timeouts are thrown by HttpClient itself before we get
 * here — those are caught and re-thrown as Transient by StankoffClient.
 *
 * Retry-After header (RFC 7231 §7.1.3) is NOT yet honoured — Messenger's
 * retry_strategy uses its own exp-backoff. If Stankoff begins emitting
 * Retry-After on 429/503, this is a candidate enhancement (would attach a
 * DelayStamp to the redelivered message).
 */
final class ErrorClassifier
{
    /** errorCodes that are transient even though their HTTP status is 401 */
    private const TRANSIENT_401_CODES = [
        'INVALID_TIMESTAMP',
        'REPLAY_WINDOW_EXCEEDED',
    ];

    /**
     * 4xx statuses that are explicitly transient. Everything else 4xx defaults
     * to permanent (see classify()).
     */
    private const TRANSIENT_4XX_STATUSES = [
        408,  // Request Timeout
        429,  // Too Many Requests
    ];

    public static function classify(int $status, ?string $errorCode, string $body): StankoffApiException
    {
        if ($status >= 500) {
            return new StankoffTransientException(
                "Stankoff returned {$status} (server error)",
                httpStatus: $status,
                errorCode: $errorCode,
                responseBody: self::trim($body),
            );
        }

        if (in_array($status, self::TRANSIENT_4XX_STATUSES, true)) {
            return new StankoffTransientException(
                "Stankoff returned {$status} (transient — back off and retry)",
                httpStatus: $status,
                errorCode: $errorCode,
                responseBody: self::trim($body),
            );
        }

        if ($status === 401 && $errorCode !== null && in_array($errorCode, self::TRANSIENT_401_CODES, true)) {
            return new StankoffTransientException(
                "Stankoff returned 401 {$errorCode} (likely clock drift, retrying)",
                httpStatus: 401,
                errorCode: $errorCode,
                responseBody: self::trim($body),
            );
        }

        // anything else 4xx → permanent
        return new StankoffPermanentException(
            "Stankoff rejected request: {$status} " . ($errorCode ?? 'UNKNOWN'),
            httpStatus: $status,
            errorCode: $errorCode,
            responseBody: self::trim($body),
        );
    }

    private static function trim(string $body): string
    {
        return mb_strlen($body) > 2000 ? mb_substr($body, 0, 2000) . '…[truncated]' : $body;
    }
}
