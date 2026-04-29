<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Stankoff integration scaffolding:
 *  - integration_outbox_event: source-of-truth for delivery state per support ticket
 *  - messenger_messages: Symfony Messenger Doctrine transport queue
 *
 * Both tables are NEW (additive). No existing tables/columns are modified.
 */
final class Version20260429120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stankoff integration: integration_outbox_event + messenger_messages tables';
    }

    public function up(Schema $schema): void
    {
        // ---------- outbox ----------
        $this->addSql(<<<'SQL'
            CREATE TABLE integration_outbox_event (
                id UUID NOT NULL,
                event_type VARCHAR(64) NOT NULL,
                aggregate_type VARCHAR(64) NOT NULL,
                aggregate_id INT NOT NULL,
                idempotency_key VARCHAR(64) NOT NULL,
                status VARCHAR(32) NOT NULL,
                uploaded_file_ids JSON NOT NULL,
                attempts_count INT NOT NULL DEFAULT 0,
                last_error TEXT DEFAULT NULL,
                last_attempt_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                succeeded_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);

        $this->addSql('CREATE UNIQUE INDEX uniq_outbox_idempotency_key ON integration_outbox_event (idempotency_key)');
        $this->addSql('CREATE INDEX idx_outbox_status_created ON integration_outbox_event (status, created_at)');
        $this->addSql('CREATE INDEX idx_outbox_aggregate ON integration_outbox_event (aggregate_type, aggregate_id)');

        // ---------- messenger ----------
        // Standard layout from Symfony Messenger Doctrine transport. Auto_setup is
        // disabled in env (auto_setup=0) because we want a controlled migration.
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (
                id BIGSERIAL NOT NULL,
                body TEXT NOT NULL,
                headers TEXT NOT NULL,
                queue_name VARCHAR(190) NOT NULL,
                created_at TIMESTAMP(6) WITHOUT TIME ZONE NOT NULL,
                available_at TIMESTAMP(6) WITHOUT TIME ZONE NOT NULL,
                delivered_at TIMESTAMP(6) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY(id)
            )
        SQL);

        $this->addSql('CREATE INDEX idx_msg_queue_name ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX idx_msg_available_at ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX idx_msg_delivered_at ON messenger_messages (delivered_at)');

        // pg-specific NOTIFY trigger (used by Messenger to wake workers without polling)
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify('messenger_messages', NEW.queue_name::text);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TRIGGER notify_trigger
            AFTER INSERT OR UPDATE ON messenger_messages
            FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages');
        $this->addSql('DROP FUNCTION IF EXISTS notify_messenger_messages()');
        $this->addSql('DROP TABLE IF EXISTS messenger_messages');
        $this->addSql('DROP TABLE IF EXISTS integration_outbox_event');
    }
}
