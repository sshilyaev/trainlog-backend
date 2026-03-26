<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260319170000_add_weight_to_profiles extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add current weight fields to profiles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE profiles ADD weight DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE profiles ADD weight_updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE profiles DROP weight');
        $this->addSql('ALTER TABLE profiles DROP weight_updated_at');
    }
}

