<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260114084905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE support_ticket_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE support_ticket_comment_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE support_ticket (id INT NOT NULL, subject VARCHAR(255) NOT NULL, description TEXT NOT NULL, author_name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, order_id INT DEFAULT NULL, order_data JSON DEFAULT NULL, process_instance_key VARCHAR(255) DEFAULT NULL, user_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_1F5A4D53A76ED395 ON support_ticket (user_id)');
        $this->addSql('CREATE TABLE support_ticket_comment (id INT NOT NULL, comment TEXT NOT NULL, closing_reason VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, support_ticket_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_51EC784FC6D2DC64 ON support_ticket_comment (support_ticket_id)');
        $this->addSql('CREATE INDEX IDX_51EC784FA76ED395 ON support_ticket_comment (user_id)');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, email VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('ALTER TABLE support_ticket ADD CONSTRAINT FK_1F5A4D53A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE support_ticket_comment ADD CONSTRAINT FK_51EC784FC6D2DC64 FOREIGN KEY (support_ticket_id) REFERENCES support_ticket (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE support_ticket_comment ADD CONSTRAINT FK_51EC784FA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE support_ticket_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE support_ticket_comment_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('ALTER TABLE support_ticket DROP CONSTRAINT FK_1F5A4D53A76ED395');
        $this->addSql('ALTER TABLE support_ticket_comment DROP CONSTRAINT FK_51EC784FC6D2DC64');
        $this->addSql('ALTER TABLE support_ticket_comment DROP CONSTRAINT FK_51EC784FA76ED395');
        $this->addSql('DROP TABLE support_ticket');
        $this->addSql('DROP TABLE support_ticket_comment');
        $this->addSql('DROP TABLE "user"');
    }
}
