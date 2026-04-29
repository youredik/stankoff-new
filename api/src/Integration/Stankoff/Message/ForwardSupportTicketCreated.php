<?php

declare(strict_types=1);

namespace App\Integration\Stankoff\Message;

/**
 * Async message dispatched after a SupportTicket is persisted. Carries only the
 * outbox row id; the handler reads the full state (including idempotency_key,
 * uploaded_file_ids, attempts_count) from the outbox to ensure correctness
 * across retries.
 */
final readonly class ForwardSupportTicketCreated
{
    public function __construct(
        public string $outboxEventId,
    ) {
    }
}
