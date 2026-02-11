<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add accepted_at column to support_ticket and backfill from first IN_PROGRESS comment';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE support_ticket ADD accepted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        $this->addSql("
            UPDATE support_ticket t
            SET accepted_at = (
                SELECT MIN(c.created_at)
                FROM support_ticket_comment c
                WHERE c.support_ticket_id = t.id
                  AND c.status = 'in_progress'
            )
            WHERE t.status IN ('in_progress', 'postponed', 'completed')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE support_ticket DROP COLUMN accepted_at');
    }
}
