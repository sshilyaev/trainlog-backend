<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250317100000_profiles_extra_fields extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add date_of_birth, phone_number, telegram_username, notes, height, owner_coach_profile_id to profiles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE profiles ADD date_of_birth DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE profiles ADD phone_number VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE profiles ADD telegram_username VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE profiles ADD notes TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE profiles ADD height DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE profiles ADD owner_coach_profile_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN profiles.owner_coach_profile_id IS \'Managed trainee: profile created by coach without app\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE profiles DROP date_of_birth');
        $this->addSql('ALTER TABLE profiles DROP phone_number');
        $this->addSql('ALTER TABLE profiles DROP telegram_username');
        $this->addSql('ALTER TABLE profiles DROP notes');
        $this->addSql('ALTER TABLE profiles DROP height');
        $this->addSql('ALTER TABLE profiles DROP owner_coach_profile_id');
    }
}
