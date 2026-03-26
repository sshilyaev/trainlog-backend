<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250314120000_profile_merged_fields_and_events_table extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add merged_into_profile_id and merged_at to profiles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE profiles ADD merged_into_profile_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE profiles ADD merged_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE profiles DROP merged_into_profile_id');
        $this->addSql('ALTER TABLE profiles DROP merged_at');
    }
}
