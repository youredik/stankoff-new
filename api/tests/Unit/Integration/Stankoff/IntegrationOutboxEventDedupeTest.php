<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Stankoff;

use App\Integration\Stankoff\Outbox\IntegrationOutboxEvent;
use App\Integration\Stankoff\Outbox\OutboxStatus;
use PHPUnit\Framework\TestCase;

final class IntegrationOutboxEventDedupeTest extends TestCase
{
    private function event(): IntegrationOutboxEvent
    {
        return new IntegrationOutboxEvent(
            eventType: 'stankoff.support_ticket.created.v1',
            aggregateType: 'SupportTicket',
            aggregateId: 42,
            idempotencyKey: str_repeat('a', 32),
        );
    }

    public function testRecordDedupeCheckSetsTimestampAndStatus(): void
    {
        $e = $this->event();
        self::assertNull($e->lastDedupeCheckAt);
        self::assertNull($e->dedupeRemoteStatus);

        $e->recordDedupeCheck('processed');

        self::assertNotNull($e->lastDedupeCheckAt);
        self::assertSame('processed', $e->dedupeRemoteStatus);
    }

    public function testMarkRemotelyFailedLiftsLocalStatus(): void
    {
        $e = $this->event();
        $e->markSucceeded(); // simulate prior local success
        self::assertSame(OutboxStatus::SUCCEEDED, $e->status);

        $e->recordDedupeCheck('failed');
        $e->markRemotelyFailed('upstream consumer rejected: SCHEMA_VIOLATION');

        self::assertSame(OutboxStatus::PERMANENTLY_FAILED, $e->status);
        self::assertSame('upstream consumer rejected: SCHEMA_VIOLATION', $e->lastError);
    }

    public function testRecordDedupeCheckIsIdempotentOnRepeat(): void
    {
        $e = $this->event();
        $e->recordDedupeCheck('pending');
        $first = $e->lastDedupeCheckAt;
        usleep(2000);
        $e->recordDedupeCheck('pending');

        self::assertNotEquals($first, $e->lastDedupeCheckAt, 'timestamp must advance on each poll');
        self::assertSame('pending', $e->dedupeRemoteStatus);
    }

    public function testProcessedDoesNotLiftToFailed(): void
    {
        $e = $this->event();
        $e->markSucceeded();
        $e->recordDedupeCheck('processed');

        // No mark-failed call → status stays succeeded
        self::assertSame(OutboxStatus::SUCCEEDED, $e->status);
        self::assertNull($e->lastError);
    }

    public function testDeferredIsRecordedAsNonTerminal(): void
    {
        // 'deferred' = Stankoff initial state, set by webhook receiver inside
        // persist transaction. Async consumer transitions it to a terminal state
        // (per partner 2026-04-29 commit d498845). We must NOT lift on 'deferred',
        // and the row must remain pollable.
        $e = $this->event();
        $e->markSucceeded();
        $e->recordDedupeCheck('deferred');

        self::assertSame(OutboxStatus::SUCCEEDED, $e->status);
        self::assertNull($e->lastError);
        self::assertSame('deferred', $e->dedupeRemoteStatus);
    }
}
