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
use Symfony\Component\Messenger\MessageBusInterface;
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

            $this->bus->dispatch(new ForwardSupportTicketCreated($outbox->id->toRfc4122()));
        } catch (\Throwable $e) {
            $this->logger->critical('stankoff: integration dispatch failed, ticket created without outbox', [
                'ticketId' => $ticket->getId(),
                'exception' => $e,
            ]);
        }
    }
}
