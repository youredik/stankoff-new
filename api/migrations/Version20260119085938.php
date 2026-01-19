<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260119085938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE support_ticket_media_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE support_ticket_media (id INT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(255) NOT NULL, size BIGINT NOT NULL, path VARCHAR(500) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, support_ticket_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_79A7A0BAC6D2DC64 ON support_ticket_media (support_ticket_id)');
        $this->addSql('ALTER TABLE support_ticket_media ADD CONSTRAINT FK_79A7A0BAC6D2DC64 FOREIGN KEY (support_ticket_id) REFERENCES support_ticket (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE support_ticket_media_id_seq CASCADE');
        $this->addSql('ALTER TABLE support_ticket_media DROP CONSTRAINT FK_79A7A0BAC6D2DC64');
        $this->addSql('DROP TABLE support_ticket_media');
    }
}
