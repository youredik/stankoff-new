<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\SupportTicket;
use App\Enum\SupportTicketStatus;
use App\Integration\Stankoff\Message\ForwardSupportTicketCreated;
use App\Integration\Stankoff\Outbox\IntegrationOutboxEvent;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Uid\Uuid;

/**
 * Persists a new SupportTicket. Behaviour stays byte-identical to before for
 * the API contract (POST /support_tickets returns 201 with the same body).
 *
 * Optional Stankoff integration block (when STANKOFF_INTEGRATION_ENABLED=true):
 *  - Adds an outbox row + dispatches an async Messenger job AFTER the ticket
 *    is flushed.
 *  - Wrapped in try/catch — any failure here is logged at CRITICAL but MUST
 *    NOT propagate. The user's POST must still return 201 even if the
 *    integration is broken / DB is full / messenger transport is down.
 *  - Trade-off: if the integration block fails between ticket-flush and
 *    dispatch, we'll have a ticket without an outbox row. Ops sees the
 *    CRITICAL log, can backfill via a maintenance command. Better than a
 *    failed POST for the user.
 *
 * @implements ProcessorInterface<SupportTicket, SupportTicket>
 */
final class SupportTicketCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'bool:STANKOFF_INTEGRATION_ENABLED')] private readonly bool $integrationEnabled,
        /**
         * Delay (ms) before the worker picks up the dispatched message. Empirically
         * needed because media (POST /support_tickets/{id}/media) arrive AFTER ticket
         * creation: real-world observation 2026-04-29 showed an mp4 attachment
         * arriving 4 seconds after the ticket. Without delay, any media uploaded
         * post-ticket-create is missed by the webhook (worker handles in <1s).
         *
         * Default 30 sec covers 99% of realistic upload patterns including 100MB
         * files on slow connections. Configurable via env so it can be lowered for
         * dev/test (e.g. 0 for instant smoke probes).
         */
        #[Autowire(env: 'int:STANKOFF_DISPATCH_DELAY_MS')] private readonly int $dispatchDelayMs = 30000,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): SupportTicket {
        assert($data instanceof SupportTicket);

        $data->createdAt = new DateTimeImmutable();
        $data->status = SupportTicketStatus::NEW;

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        if ($this->integrationEnabled) {
            $this->scheduleStankoffForward($data);
        }

        return $data;
    }

    private function scheduleStankoffForward(SupportTicket $ticket): void
    {
        try {
            // 32-char hex from UUIDv7 binary — fits Stankoff's [8,128] requirement,
            // sortable, unique, no DB round-trip.
            $idempotencyKey = bin2hex(Uuid::v7()->toBinary());

            $outbox = new IntegrationOutboxEvent(
                eventType: 'stankoff.support_ticket.created.v1',
                aggregateType: 'SupportTicket',
                aggregateId: (int)$ticket->getId(),
                idempotencyKey: $idempotencyKey,
            );
            $this->entityManager->persist($outbox);
            $this->entityManager->flush();

            $message = new ForwardSupportTicketCreated($outbox->id->toRfc4122());
            $envelope = $this->dispatchDelayMs > 0
                ? new Envelope($message, [new DelayStamp($this->dispatchDelayMs)])
                : new Envelope($message);
            $this->bus->dispatch($envelope);
        } catch (\Throwable $e) {
            $this->logger->critical('stankoff: integration dispatch failed, ticket created without outbox', [
                'ticketId' => $ticket->getId(),
                'exception' => $e,
            ]);
        }
    }
}
