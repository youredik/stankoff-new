<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260128073755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE support_ticket ADD status VARCHAR(255) DEFAULT NULL');
        $this->addSql("
            UPDATE support_ticket st
            SET status = COALESCE((
                SELECT stc.status
                FROM support_ticket_comment stc
                WHERE stc.support_ticket_id = st.id
                ORDER BY stc.created_at DESC
                LIMIT 1
            ), 'new')
            WHERE st.status IS NULL
        ");
        $this->addSql('ALTER TABLE support_ticket ALTER status SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE support_ticket ALTER status TYPE VARCHAR(50)');
        $this->addSql('ALTER TABLE support_ticket ALTER status DROP NOT NULL');
    }
}
