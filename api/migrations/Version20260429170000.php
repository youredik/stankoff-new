<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Update dedupe-check partial index after partner clarified the status enum
 * (commit d498845, 2026-04-29):
 *
 *   processed | deferred | failed | dlq
 *
 * 'pending' was a doc bug on their side and never existed in source-of-truth
 * schema (Z.enum in packages/shared/src/schemas/integration.ts). The actual
 * non-terminal initial state is 'deferred' (set in webhook receiver inside
 * persist transaction). We keep 'unknown' for our local 404→TTL handling.
 */
final class Version20260429170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stankoff: switch dedupe-check partial-index from \'pending\' to \'deferred\' (partner enum correction)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_outbox_dedupe_check');
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_outbox_dedupe_check
            ON integration_outbox_event (succeeded_at)
            WHERE status = 'succeeded'
              AND (last_dedupe_check_at IS NULL OR dedupe_remote_status IN ('deferred', 'unknown'))
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_outbox_dedupe_check');
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_outbox_dedupe_check
            ON integration_outbox_event (succeeded_at)
            WHERE status = 'succeeded'
              AND (last_dedupe_check_at IS NULL OR dedupe_remote_status IN ('pending', 'unknown'))
        SQL);
    }
}
