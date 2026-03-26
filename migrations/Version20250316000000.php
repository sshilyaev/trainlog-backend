<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250316000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add archived column to coach_trainee_links';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE coach_trainee_links ADD archived BOOLEAN NOT NULL DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE coach_trainee_links DROP archived');
    }
}
