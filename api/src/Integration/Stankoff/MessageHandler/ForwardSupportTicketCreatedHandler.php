<?php

declare(strict_types=1);

namespace App\Integration\Stankoff\MessageHandler;

use App\Entity\SupportTicket;
use App\Integration\Stankoff\Client\StankoffClient;
use App\Integration\Stankoff\Client\StankoffPermanentException;
use App\Integration\Stankoff\Client\StankoffTransientException;
use App\Integration\Stankoff\Files\FilesUploader;
use App\Integration\Stankoff\Message\ForwardSupportTicketCreated;
use App\Integration\Stankoff\Outbox\IntegrationOutboxEvent;
use App\Integration\Stankoff\Outbox\IntegrationOutboxEventRepository;
use App\Integration\Stankoff\Outbox\OutboxStatus;
use App\Integration\Stankoff\Payload\PayloadBuilder;
use App\Repository\SupportTicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles asynchronous delivery of a "support_ticket.created" event to Stankoff.
 *
 * Flow:
 *  1. Load outbox row by id; bail out if already SUCCEEDED or PERMANENTLY_FAILED
 *     (idempotent guard against double-delivery of a re-dispatched message).
 *  2. Mark IN_PROGRESS, increment attemptsCount, persist immediately.
 *  3. (If files enabled) upload SupportTicketMedia not yet uploaded.
 *  4. Build payload (with attachment_ids).
 *  5. SHADOW_MODE: log payload + headers, mark SUCCEEDED, return.
 *     Otherwise: HTTP send.
 *  6. On success: mark SUCCEEDED.
 *     On Permanent: mark PERMANENTLY_FAILED, exception halts retry.
 *     On Transient: revert to PENDING, throw — Messenger retries with backoff.
 *
 * The handler is the only writer of outbox state during processing. The Processor
 * is the only writer at creation. No other code path mutates outbox.
 */
#[AsMessageHandler]
final class ForwardSupportTicketCreatedHandler
{
    public function __construct(
        private readonly IntegrationOutboxEventRepository $outboxRepo,
        private readonly SupportTicketRepository $ticketRepo,
        private readonly EntityManagerInterface $em,
        private readonly PayloadBuilder $payloadBuilder,
        private readonly StankoffClient $stankoffClient,
        private readonly FilesUploader $filesUploader,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'bool:STANKOFF_SHADOW_MODE')] private readonly bool $shadowMode,
        #[Autowire(env: 'bool:STANKOFF_FILES_ENABLED')] private readonly bool $filesEnabled,
    ) {
    }

    public function __invoke(ForwardSupportTicketCreated $message): void
    {
        $outbox = $this->outboxRepo->findByIdString($message->outboxEventId);
        if ($outbox === null) {
            // Should never happen — outbox row is created in the same transaction
            // as the dispatch. If it does, treat as permanent (no row to retry against).
            throw new StankoffPermanentException(
                "Outbox row not found: {$message->outboxEventId}",
                errorCode: 'OUTBOX_MISSING',
            );
        }

        if ($outbox->status === OutboxStatus::SUCCEEDED) {
            $this->logger->info('stankoff: outbox already succeeded, skipping (idempotent)', [
                'outboxId' => $message->outboxEventId,
            ]);
            return;
        }
        if ($outbox->status === OutboxStatus::PERMANENTLY_FAILED) {
            $this->logger->info('stankoff: outbox permanently failed, skipping', [
                'outboxId' => $message->outboxEventId,
            ]);
            return;
        }

        $ticket = $this->ticketRepo->find($outbox->aggregateId);
        if (!$ticket instanceof SupportTicket) {
            // Ticket was deleted after dispatch. We can't recover — mark permanent.
            $outbox->markPermanentlyFailed('Ticket gone before forwarding');
            $this->em->flush();
            throw new StankoffPermanentException(
                "SupportTicket id={$outbox->aggregateId} not found",
                errorCode: 'AGGREGATE_MISSING',
            );
        }

        $outbox->markInProgress();
        $this->em->flush();

        try {
            $attachmentIds = [];
            if ($this->filesEnabled && !$ticket->media->isEmpty()) {
                $attachmentIds = $this->filesUploader->uploadAll(
                    $ticket->media,
                    $outbox->uploadedFileIds,
                    function (int $mediaId, string $fileId) use ($outbox): void {
                        $outbox->rememberUploadedFile($mediaId, $fileId);
                        $this->em->flush();
                    },
                );
            }

            $payload = $this->payloadBuilder->build($ticket, $attachmentIds);

            if ($this->shadowMode) {
                $this->logger->info('stankoff: SHADOW MODE — payload built but NOT sent', [
                    'outboxId' => $message->outboxEventId,
                    'idempotencyKey' => $outbox->idempotencyKey,
                    'payload' => self::maskPii($payload),
                ]);
                $outbox->markSucceeded();
                $this->em->flush();
                return;
            }

            $response = $this->stankoffClient->sendInbox($payload, $outbox->idempotencyKey);

            $outbox->markSucceeded();
            $this->em->flush();

            $this->logger->info('stankoff: webhook delivered', [
                'outboxId' => $message->outboxEventId,
                'ticketId' => $ticket->getId(),
                'response' => $response,
            ]);
        } catch (StankoffPermanentException $e) {
            $outbox->markPermanentlyFailed(self::summarizeError($e));
            $this->em->flush();
            $this->logger->error('stankoff: permanent failure, NOT retrying', [
                'outboxId' => $message->outboxEventId,
                'httpStatus' => $e->getHttpStatus(),
                'errorCode' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ]);
            throw $e; // halts Messenger retry (extends UnrecoverableMessageHandlingException)
        } catch (StankoffTransientException $e) {
            $outbox->recordTransientFailure(self::summarizeError($e));
            $this->em->flush();
            $this->logger->warning('stankoff: transient failure, will retry', [
                'outboxId' => $message->outboxEventId,
                'httpStatus' => $e->getHttpStatus(),
                'errorCode' => $e->getErrorCode(),
                'attempt' => $outbox->attemptsCount,
                'message' => $e->getMessage(),
            ]);
            throw $e; // Messenger will retry per retry_strategy
        } catch (\Throwable $e) {
            // Unknown failure (bug in our code, DB, etc.). Treat as transient by
            // default — better to retry than to mark permanent on a transient bug.
            $outbox->recordTransientFailure('Unexpected: ' . $e->getMessage());
            $this->em->flush();
            $this->logger->error('stankoff: unexpected handler error, will retry', [
                'outboxId' => $message->outboxEventId,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Strips/masks PII from payload before logging. Phone -> "+7***1234",
     * email -> "i***@example.ru". Reduces blast radius of an accidental log dump.
     *
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private static function maskPii(array $payload): array
    {
        $inner = $payload['payload'] ?? null;
        if (is_array($inner)) {
            if (isset($inner['client_contact_phone']) && is_string($inner['client_contact_phone'])) {
                $inner['client_contact_phone'] = self::maskPhone($inner['client_contact_phone']);
            }
            if (isset($inner['client_contact_email']) && is_string($inner['client_contact_email'])) {
                $inner['client_contact_email'] = self::maskEmail($inner['client_contact_email']);
            }
            $payload['payload'] = $inner;
        }
        return $payload;
    }

    private static function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        $tail = mb_substr($digits, -4);
        return preg_replace('/\d/', '*', mb_substr($phone, 0, -4)) . $tail;
    }

    private static function maskEmail(string $email): string
    {
        $at = strpos($email, '@');
        if ($at === false || $at < 1) {
            return '***';
        }
        $first = mb_substr($email, 0, 1);
        $domain = mb_substr($email, $at);
        return $first . '***' . $domain;
    }

    private static function summarizeError(\Throwable $e): string
    {
        return mb_substr($e->getMessage(), 0, 1000);
    }
}
