<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250115210748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE key_value_store ADD key VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE key_value_store ADD value JSON NOT NULL');
        $this->addSql('ALTER TABLE key_value_store DROP data');
        $this->addSql('ALTER TABLE key_value_store RENAME COLUMN entity_id TO user_id');
        $this->addSql('ALTER TABLE key_value_store ADD CONSTRAINT FK_5B9FDE1FA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_5B9FDE1FA76ED395 ON key_value_store (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE key_value_store DROP CONSTRAINT FK_5B9FDE1FA76ED395');
        $this->addSql('DROP INDEX IDX_5B9FDE1FA76ED395');
        $this->addSql('ALTER TABLE key_value_store ADD data JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE key_value_store DROP key');
        $this->addSql('ALTER TABLE key_value_store DROP value');
        $this->addSql('ALTER TABLE key_value_store RENAME COLUMN user_id TO entity_id');
    }
}
