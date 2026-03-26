<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260319120000_add_cancelled_at_to_visits extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cancelledAt column to visits for audit';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE visits ADD cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE visits DROP cancelled_at');
    }
}

