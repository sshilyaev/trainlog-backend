<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250317120000_add_developer_mode_to_profiles extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add developer_mode flag to profiles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE profiles ADD developer_mode BOOLEAN DEFAULT FALSE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE profiles DROP developer_mode');
    }
}

