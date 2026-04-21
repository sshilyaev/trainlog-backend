<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421130000_add_event_type_to_events extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add event_type to events with default general';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE events ADD event_type VARCHAR(32) DEFAULT 'general' NOT NULL");
        $this->addSql("UPDATE events SET event_type = 'general' WHERE event_type IS NULL OR event_type = ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE events DROP event_type');
    }
}

