<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250316100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add kind, start_date, end_date, freeze_days to memberships for unlimited by days';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE memberships ADD kind VARCHAR(20) NOT NULL DEFAULT \'by_visits\'');
        $this->addSql('ALTER TABLE memberships ADD start_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE memberships ADD end_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE memberships ADD freeze_days INT NOT NULL DEFAULT 0');
        $this->addSql('COMMENT ON COLUMN memberships.kind IS \'by_visits or unlimited\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE memberships DROP kind');
        $this->addSql('ALTER TABLE memberships DROP start_date');
        $this->addSql('ALTER TABLE memberships DROP end_date');
        $this->addSql('ALTER TABLE memberships DROP freeze_days');
    }
}
