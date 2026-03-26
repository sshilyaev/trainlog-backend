<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250314110000_create_events extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create events table (calendar events for coach-trainee pair)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE events (
            id VARCHAR(36) NOT NULL,
            coach_profile_id VARCHAR(36) NOT NULL,
            trainee_profile_id VARCHAR(36) NOT NULL,
            title VARCHAR(255) NOT NULL,
            date DATE NOT NULL,
            description TEXT DEFAULT NULL,
            remind BOOLEAN NOT NULL DEFAULT false,
            color_hex VARCHAR(12) DEFAULT NULL,
            is_cancelled BOOLEAN NOT NULL DEFAULT false,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_events_coach_trainee_date ON events (coach_profile_id, trainee_profile_id, date)');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_events_coach FOREIGN KEY (coach_profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_events_trainee FOREIGN KEY (trainee_profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE events DROP CONSTRAINT FK_events_coach');
        $this->addSql('ALTER TABLE events DROP CONSTRAINT FK_events_trainee');
        $this->addSql('DROP TABLE events');
    }
}
