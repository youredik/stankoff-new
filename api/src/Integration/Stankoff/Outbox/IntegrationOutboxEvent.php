<?php

declare(strict_types=1);

namespace App\Integration\Stankoff\Outbox;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Persistent record of an integration event scheduled for delivery to Stankoff.
 *
 * Created in the same DB transaction as the source aggregate (e.g. SupportTicket)
 * so the outbox row and the aggregate are atomic. The Messenger handler reads this
 * row, performs the HTTP call, and updates status. State here is the source of
 * truth for delivery — Messenger queue is just a notification mechanism.
 */
#[ORM\Entity(repositoryClass: IntegrationOutboxEventRepository::class)]
#[ORM\Table(name: 'integration_outbox_event')]
#[ORM\Index(name: 'idx_outbox_status_created', columns: ['status', 'created_at'])]
#[ORM\Index(name: 'idx_outbox_aggregate', columns: ['aggregate_type', 'aggregate_id'])]
class IntegrationOutboxEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    public Uuid $id;

    #[ORM\Column(type: Types::STRING, length: 64)]
    public string $eventType;

    #[ORM\Column(type: Types::STRING, length: 64)]
    public string $aggregateType;

    #[ORM\Column(type: Types::INTEGER)]
    public int $aggregateId;

    #[ORM\Column(type: Types::STRING, length: 64, unique: true)]
    public string $idempotencyKey;

    #[ORM\Column(type: Types::STRING, length: 32, enumType: OutboxStatus::class)]
    public OutboxStatus $status = OutboxStatus::PENDING;

    /**
     * Map of mediaId => fileId for files already uploaded to Stankoff Files API.
     * Keyed by source SupportTicketMedia.id so retries skip exactly what was
     * already done, even if a different media item was added/removed in between.
     *
     * @var array<int, string>
     */
    #[ORM\Column(type: Types::JSON)]
    public array $uploadedFileIds = [];

    #[ORM\Column(type: Types::INTEGER)]
    public int $attemptsCount = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $lastError = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $lastAttemptAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $succeededAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $createdAt;

    public function __construct(
        string $eventType,
        string $aggregateType,
        int $aggregateId,
        string $idempotencyKey,
    ) {
        $this->id = Uuid::v7();
        $this->eventType = $eventType;
        $this->aggregateType = $aggregateType;
        $this->aggregateId = $aggregateId;
        $this->idempotencyKey = $idempotencyKey;
        $this->createdAt = new DateTimeImmutable();
    }

    public function markInProgress(): void
    {
        $this->status = OutboxStatus::IN_PROGRESS;
        $this->attemptsCount++;
        $this->lastAttemptAt = new DateTimeImmutable();
    }

    public function markSucceeded(): void
    {
        $this->status = OutboxStatus::SUCCEEDED;
        $this->succeededAt = new DateTimeImmutable();
        $this->lastError = null;
    }

    public function markPermanentlyFailed(string $error): void
    {
        $this->status = OutboxStatus::PERMANENTLY_FAILED;
        $this->lastError = $error;
    }

    public function recordTransientFailure(string $error): void
    {
        $this->status = OutboxStatus::PENDING;
        $this->lastError = $error;
    }

    public function rememberUploadedFile(int $mediaId, string $fileId): void
    {
        $this->uploadedFileIds[$mediaId] = $fileId;
    }
}
