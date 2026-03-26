<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250314100000_add_chest_to_measurements extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add chest column to measurements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE measurements ADD chest DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE measurements DROP chest');
    }
}
