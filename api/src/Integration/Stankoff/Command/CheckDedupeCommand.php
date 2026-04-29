<?php

declare(strict_types=1);

namespace App\Integration\Stankoff\Command;

use App\Integration\Stankoff\Client\StankoffClient;
use App\Integration\Stankoff\Outbox\IntegrationOutboxEventRepository;
use App\Notification\TelegramAlerter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Closes the lazy-ingest gap (S3 in stress-test-findings.md): a 202 from Stankoff
 * means "received and queued", not "stored". Their async consumer may later mark
 * the dedupe-row as `failed` or `dlq` — without polling, we never know.
 *
 * This command scans locally-succeeded outbox rows that haven't been polled (or
 * whose remote status was 'pending'), asks Stankoff /pull/dedupe per row, and
 * lifts to permanently_failed if the remote consumer reports failure.
 *
 * Designed to be run periodically (e.g. every 5 min via systemd timer or cron).
 * Idempotent: only processes rows that need polling; safe to run multiple times.
 *
 * Usage:
 *   php bin/console app:stankoff:check-dedupe [--limit=N] [--older-than-sec=N] [--dry-run]
 */
#[AsCommand(
    name: 'app:stankoff:check-dedupe',
    description: 'Poll Stankoff /pull/dedupe for locally-succeeded outbox rows and lift remote failures.',
)]
final class CheckDedupeCommand extends Command
{
    /**
     * Stankoff dedupe-row TTL is 7 days (confirmed by partner-doc 2026-04-29:
     * `ON receivedAt INTERVAL P7D` in YDB scheme). After TTL the row is
     * evicted and we'll get 404 forever — at that point we mark
     * permanently_failed with an "outcome unknown" reason so the row stops
     * being polled.
     */
    private const TTL_SECONDS = 7 * 24 * 3600;

    public function __construct(
        private readonly IntegrationOutboxEventRepository $outboxRepo,
        private readonly StankoffClient $stankoffClient,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly TelegramAlerter $alerter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Max rows to check this run', '50')
            ->addOption('older-than-sec', null, InputOption::VALUE_OPTIONAL, 'Only check rows succeeded > N sec ago (give consumer time)', '60')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Don\'t persist any change');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = max(1, (int) $input->getOption('limit'));
        $olderThanSec = max(0, (int) $input->getOption('older-than-sec'));
        $dryRun = (bool) $input->getOption('dry-run');

        $rows = $this->outboxRepo->findSucceededNeedingDedupeCheck($olderThanSec, $limit);
        $output->writeln(sprintf('Found %d outbox row(s) to check (limit=%d, minAgeSec=%d, dryRun=%s)',
            count($rows), $limit, $olderThanSec, $dryRun ? 'YES' : 'no'));

        // Per partner enum (2026-04-29 commit d498845): processed | deferred | failed | dlq.
        // 'unknown' is our local synthetic for 404 (row evicted or mapping bug). 'error' counts exceptions.
        $stats = ['processed' => 0, 'deferred' => 0, 'failed' => 0, 'dlq' => 0, 'unknown' => 0, 'error' => 0];

        foreach ($rows as $row) {
            $idemKey = $row->idempotencyKey;
            try {
                $result = $this->stankoffClient->getDedupeStatus($idemKey);
                $remote = (string) $result['resultStatus'];
                $stats[$remote] = ($stats[$remote] ?? 0) + 1;

                $output->writeln(sprintf(
                    '  outbox=%s aggregate=%s key=%s -> %s%s',
                    (string) $row->id,
                    (string) $row->aggregateId,
                    substr($idemKey, 0, 16) . '…',
                    $remote,
                    isset($result['errorMessage']) && $result['errorMessage']
                        ? ' (' . self::truncate((string) $result['errorMessage'], 80) . ')'
                        : '',
                ));

                if ($dryRun) {
                    continue;
                }

                $row->recordDedupeCheck($remote);

                if ($remote === 'failed' || $remote === 'dlq') {
                    $msg = is_string($result['errorMessage'] ?? null) && $result['errorMessage'] !== ''
                        ? "remote {$remote}: " . (string) $result['errorMessage']
                        : "remote {$remote}: (no errorMessage from Stankoff)";
                    $row->markRemotelyFailed($msg);
                    $this->logger->error('stankoff: outbox lifted to permanently_failed by dedupe poll', [
                        'outboxId' => (string) $row->id,
                        'aggregateId' => $row->aggregateId,
                        'idempotencyKey' => $idemKey,
                        'remoteStatus' => $remote,
                        'errorMessage' => $result['errorMessage'] ?? null,
                    ]);
                    $this->alerter->notify('🔥 Dedupe lift в permanently_failed', [
                        'ticket' => $row->aggregateId,
                        'remoteStatus' => $remote,
                        'errorMessage' => is_string($result['errorMessage'] ?? null)
                            ? mb_substr((string) $result['errorMessage'], 0, 200)
                            : null,
                    ]);
                } elseif ($remote === 'unknown' && $row->succeededAt !== null) {
                    // 404 from Stankoff. Two semantics per partner-doc 2026-04-29:
                    //   - row younger than ~5min: likely a mapping bug on their consumer
                    //     side (we dedup'd, they didn't write). Don't give up — keep polling.
                    //   - row older than TTL (7d): row evicted forever. Mark permanently_failed.
                    $age = time() - $row->succeededAt->getTimestamp();
                    if ($age >= self::TTL_SECONDS) {
                        $row->markRemotelyFailed(sprintf(
                            'remote dedupe TTL evicted (age %ds), final outcome unknown',
                            $age,
                        ));
                        $this->logger->warning('stankoff: outbox lifted to permanently_failed (TTL evicted)', [
                            'outboxId' => (string) $row->id,
                            'aggregateId' => $row->aggregateId,
                            'idempotencyKey' => $idemKey,
                            'ageSeconds' => $age,
                        ]);
                        $this->alerter->notify('🪦 Dedupe TTL evicted (>7d)', [
                            'ticket' => $row->aggregateId,
                            'ageSeconds' => $age,
                        ]);
                    }
                    // else: just keep polling — recordDedupeCheck('unknown') above is enough.
                }
            } catch (\Throwable $e) {
                $stats['error']++;
                $output->writeln(sprintf('  outbox=%s ERROR: %s', (string) $row->id, $e->getMessage()));
                $this->logger->warning('stankoff: dedupe check failed for outbox row', [
                    'outboxId' => (string) $row->id,
                    'idempotencyKey' => $idemKey,
                    'exception' => $e,
                ]);
            }
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        $output->writeln(sprintf('<info>Stats: %s</info>', json_encode($stats, JSON_UNESCAPED_UNICODE)));

        return Command::SUCCESS;
    }

    private static function truncate(string $s, int $max): string
    {
        return mb_strlen($s) > $max ? mb_substr($s, 0, $max) . '…' : $s;
    }
}
