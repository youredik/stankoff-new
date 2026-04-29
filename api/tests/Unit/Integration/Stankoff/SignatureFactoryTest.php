<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Stankoff;

use App\Integration\Stankoff\Client\SignatureFactory;
use PHPUnit\Framework\TestCase;

final class SignatureFactoryTest extends TestCase
{
    public function testProducesStableHexLowercaseSignature(): void
    {
        $sf = new SignatureFactory(secret: 'topsecret');
        $sig = $sf->sign('2026-04-29T08:00:00.000Z', '{"a":1}');
        // Pre-computed: hash_hmac('sha256', '2026-04-29T08:00:00.000Z.{"a":1}', 'topsecret')
        $expected = hash_hmac('sha256', '2026-04-29T08:00:00.000Z.{"a":1}', 'topsecret');
        self::assertSame('sha256=' . $expected, $sig);
        self::assertSame(strtolower($sig), $sig, 'signature must be lowercase hex per Stankoff spec');
    }

    public function testSameInputSameOutput(): void
    {
        $sf = new SignatureFactory('s');
        self::assertSame($sf->sign('t', 'b'), $sf->sign('t', 'b'));
    }

    public function testDifferentSecretsProduceDifferentSignatures(): void
    {
        $a = (new SignatureFactory('one'))->sign('t', 'b');
        $b = (new SignatureFactory('two'))->sign('t', 'b');
        self::assertNotSame($a, $b);
    }

    public function testTimestampIncludedInSignaturePreventsReplay(): void
    {
        $sf = new SignatureFactory('s');
        // Different timestamp must yield different signature even for same body
        self::assertNotSame(
            $sf->sign('2026-04-29T08:00:00.000Z', 'b'),
            $sf->sign('2026-04-29T08:00:01.000Z', 'b'),
        );
    }

    public function testEmptySecretIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        new SignatureFactory('');
    }

    public function testTimestampNowFormatMatchesSpec(): void
    {
        $ts = SignatureFactory::timestampNow();
        // ISO 8601 with milliseconds, UTC 'Z' suffix: 2026-04-29T08:00:00.123Z
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/',
            $ts,
            'must match Stankoff timestamp spec exactly',
        );
    }
}
