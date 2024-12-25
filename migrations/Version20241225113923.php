<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify it to your needs!
 */
final class Version20241225113923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Users and Post tables with relationship';
    }

    public function up(Schema $schema): void
    {
        // Create the 'users' table (renamed from 'user')
        $this->addSql('CREATE TABLE users (
            id SERIAL PRIMARY KEY, 
            username VARCHAR(255) NOT NULL, 
            email VARCHAR(255) NOT NULL, 
            password VARCHAR(255) NOT NULL
        )');

        // Create the 'post' table
        $this->addSql('CREATE TABLE post (
            id SERIAL PRIMARY KEY, 
            title VARCHAR(255) NOT NULL, 
            content TEXT NOT NULL, 
            user_id INT NOT NULL
        )');

        // Create a foreign key relationship between 'post' and 'users'
        $this->addSql('CREATE INDEX IDX_5A8A6C8A76ED395 ON post (user_id)');
        $this->addSql('ALTER TABLE post 
            ADD CONSTRAINT FK_5A8A6C8A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop the foreign key constraint and tables
        $this->addSql('ALTER TABLE post DROP CONSTRAINT FK_5A8A6C8A76ED395');
        $this->addSql('DROP INDEX IDX_5A8A6C8A76ED395 ON post');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE users');
    }
}
