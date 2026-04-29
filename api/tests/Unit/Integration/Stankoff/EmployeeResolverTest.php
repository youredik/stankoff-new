<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Stankoff;

use App\Integration\Stankoff\Payload\EmployeeResolver;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class EmployeeResolverTest extends TestCase
{
    private string $csvPath;

    protected function setUp(): void
    {
        $this->csvPath = sys_get_temp_dir() . '/employees_test_' . uniqid('', true) . '.csv';
        file_put_contents($this->csvPath, <<<CSV
            employeeId,firstName,lastName
            87,Дмитрий,Мыслюк
            144,Виктор,Карасёв
            148,Эдуард,Сарваров
            89,Сергей,Маврин
            8,Денис,
            CSV);
    }

    protected function tearDown(): void
    {
        @unlink($this->csvPath);
    }

    private function resolver(int $fallbackId = 148): EmployeeResolver
    {
        return new EmployeeResolver(
            projectDir: sys_get_temp_dir(), // not used since we pass csvPath directly
            fallbackEmployeeId: $fallbackId,
            logger: new NullLogger(),
            csvPath: $this->csvPath,
        );
    }

    public function testExactMatch(): void
    {
        self::assertSame(87, $this->resolver()->resolve('Дмитрий Мыслюк'));
        self::assertSame(89, $this->resolver()->resolve('Сергей Маврин'));
    }

    public function testYoToEEquivalence(): void
    {
        // CSV has "Карасёв" (with ё), authorName arrives as "Карасев" (without)
        self::assertSame(144, $this->resolver()->resolve('Виктор Карасёв'));
        self::assertSame(144, $this->resolver()->resolve('Виктор Карасев'));
    }

    public function testCaseInsensitive(): void
    {
        self::assertSame(87, $this->resolver()->resolve('дмитрий мыслюк'));
        self::assertSame(87, $this->resolver()->resolve('ДМИТРИЙ МЫСЛЮК'));
    }

    public function testTrimsAndCollapsesSpaces(): void
    {
        self::assertSame(87, $this->resolver()->resolve('  Дмитрий   Мыслюк  '));
    }

    public function testReversedOrder(): void
    {
        // "Мыслюк Дмитрий" should also resolve
        self::assertSame(87, $this->resolver()->resolve('Мыслюк Дмитрий'));
    }

    public function testFallbackWhenNoMatch(): void
    {
        self::assertSame(148, $this->resolver()->resolve('Совершенно Неизвестный'));
    }

    public function testFallbackWhenEmpty(): void
    {
        self::assertSame(148, $this->resolver()->resolve(''));
        self::assertSame(148, $this->resolver()->resolve('   '));
    }

    public function testRowsWithoutLastNameAreNotIndexed(): void
    {
        // "8,Денис,," is in CSV but has no lastName — single-name lookup falls back
        self::assertSame(148, $this->resolver()->resolve('Денис'));
    }
}
