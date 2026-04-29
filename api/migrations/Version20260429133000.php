<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Stankoff integration: dedupe-check tracking on outbox rows.
 *
 * Adds two columns so the cron consumer (app:stankoff:check-dedupe) can:
 *  - find succeeded rows that haven't been pulled from /pull/dedupe yet,
 *  - record the remote consumer's verdict (processed / failed / dlq / pending),
 *  - re-poll only rows where Stankoff's consumer is still pending.
 *
 * Pure additive — no existing column changes.
 */
final class Version20260429133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stankoff: dedupe-check tracking columns on integration_outbox_event';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE integration_outbox_event ADD COLUMN last_dedupe_check_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE integration_outbox_event ADD COLUMN dedupe_remote_status VARCHAR(32) DEFAULT NULL');

        // Partial index: scan only rows we still might need to ask about.
        // succeeded rows that were never polled OR whose last remote status was
        // non-terminal — 'pending' (consumer still working) or 'unknown' (404
        // likely mapping bug; we keep polling until TTL eviction).
        // 'processed', 'failed', 'dlq' are terminal and drop out of the index.
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_outbox_dedupe_check
            ON integration_outbox_event (succeeded_at)
            WHERE status = 'succeeded'
              AND (last_dedupe_check_at IS NULL OR dedupe_remote_status IN ('pending', 'unknown'))
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_outbox_dedupe_check');
        $this->addSql('ALTER TABLE integration_outbox_event DROP COLUMN IF EXISTS dedupe_remote_status');
        $this->addSql('ALTER TABLE integration_outbox_event DROP COLUMN IF EXISTS last_dedupe_check_at');
    }
}
