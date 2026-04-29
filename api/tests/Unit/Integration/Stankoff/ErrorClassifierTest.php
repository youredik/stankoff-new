<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Stankoff;

use App\Integration\Stankoff\Client\ErrorClassifier;
use App\Integration\Stankoff\Client\StankoffPermanentException;
use App\Integration\Stankoff\Client\StankoffTransientException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ErrorClassifierTest extends TestCase
{
    #[DataProvider('transientCases')]
    public function testTransientCases(int $status, ?string $code): void
    {
        $e = ErrorClassifier::classify($status, $code, '{}');
        self::assertInstanceOf(StankoffTransientException::class, $e);
    }

    public static function transientCases(): array
    {
        return [
            '500 server error' => [500, 'INTERNAL'],
            '502 gateway' => [502, null],
            '503 unavailable' => [503, 'INTERNAL'],
            '504 timeout' => [504, null],
            '599 anything 5xx' => [599, null],
            '401 clock drift INVALID_TIMESTAMP' => [401, 'INVALID_TIMESTAMP'],
            '401 replay window exceeded' => [401, 'REPLAY_WINDOW_EXCEEDED'],
        ];
    }

    #[DataProvider('permanentCases')]
    public function testPermanentCases(int $status, ?string $code): void
    {
        $e = ErrorClassifier::classify($status, $code, '{}');
        self::assertInstanceOf(StankoffPermanentException::class, $e);
    }

    public static function permanentCases(): array
    {
        return [
            '400 generic' => [400, 'INVALID_PATH'],
            '400 missing idem key' => [400, 'MISSING_IDEMPOTENCY_KEY'],
            '401 invalid signature' => [401, 'INVALID_SIGNATURE'],
            '401 invalid signature format' => [401, 'INVALID_SIGNATURE_FORMAT'],
            '401 missing headers' => [401, 'MISSING_HEADERS'],
            '404 unknown integration' => [404, 'INTEGRATION_NOT_FOUND'],
            '413 body too large' => [413, null],
            '422 unprocessable' => [422, null],
            '418 teapot — unknown 4xx defaults to permanent' => [418, null],
        ];
    }

    public function testResponseBodyIsTruncated(): void
    {
        $longBody = str_repeat('x', 5000);
        $e = ErrorClassifier::classify(500, 'INTERNAL', $longBody);
        self::assertNotNull($e->getResponseBody());
        self::assertLessThanOrEqual(2050, mb_strlen($e->getResponseBody())); // 2000 + ellipsis tail
        self::assertStringContainsString('truncated', $e->getResponseBody());
    }
}
