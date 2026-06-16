<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617000000_create_users_table extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table for native email/password auth (replaces Firebase)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE IF NOT EXISTS users (
                id VARCHAR(36) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                PRIMARY KEY(id)
            )
        ");
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_users_email ON users (email)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_users_email ON users (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS users');
    }
}
