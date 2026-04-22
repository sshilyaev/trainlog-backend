<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422100000_add_favorite_and_period_events extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add favorite flag for coach links and period fields for events';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE coach_trainee_links ADD favorite BOOLEAN NOT NULL DEFAULT false');

        $this->addSql("ALTER TABLE events ADD mode VARCHAR(16) NOT NULL DEFAULT 'date'");
        $this->addSql('ALTER TABLE events ADD period_start DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE events ADD period_end DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE events ADD period_type VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE events ADD freeze_membership BOOLEAN NOT NULL DEFAULT false');

        $this->addSql('UPDATE events SET period_start = date, period_end = date WHERE period_start IS NULL OR period_end IS NULL');
        $this->addSql("UPDATE events SET mode = 'date' WHERE mode IS NULL OR mode = ''");
        $this->addSql('CREATE INDEX idx_events_coach_trainee_period ON events (coach_profile_id, trainee_profile_id, period_start, period_end)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_events_coach_trainee_period');
        $this->addSql('ALTER TABLE events DROP freeze_membership');
        $this->addSql('ALTER TABLE events DROP period_type');
        $this->addSql('ALTER TABLE events DROP period_end');
        $this->addSql('ALTER TABLE events DROP period_start');
        $this->addSql('ALTER TABLE events DROP mode');
        $this->addSql('ALTER TABLE coach_trainee_links DROP favorite');
    }
}
