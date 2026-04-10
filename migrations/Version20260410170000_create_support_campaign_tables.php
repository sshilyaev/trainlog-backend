<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410170000_create_support_campaign_tables extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create support campaign state/history/reward event tables for rewarded mini-game';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE support_campaign_state (id UUID NOT NULL, user_id VARCHAR(128) NOT NULL, goal_type VARCHAR(20) NOT NULL, start_weight_kg NUMERIC(6, 1) NOT NULL, current_weight_kg NUMERIC(6, 1) NOT NULL, target_weight_kg NUMERIC(6, 1) NOT NULL, status VARCHAR(20) NOT NULL, saved_clients_count INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_support_campaign_state_user ON support_campaign_state (user_id)');

        $this->addSql('CREATE TABLE support_campaign_history (id UUID NOT NULL, user_id VARCHAR(128) NOT NULL, goal_type VARCHAR(20) NOT NULL, start_weight_kg NUMERIC(6, 1) NOT NULL, target_weight_kg NUMERIC(6, 1) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_support_campaign_history_user_created ON support_campaign_history (user_id, created_at)');

        $this->addSql('CREATE TABLE support_reward_event (id UUID NOT NULL, user_id VARCHAR(128) NOT NULL, ad_provider VARCHAR(32) NOT NULL, external_event_id VARCHAR(191) NOT NULL, reward_value_kg NUMERIC(4, 1) NOT NULL, is_duplicate BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_support_reward_event_user_provider_ext ON support_reward_event (user_id, ad_provider, external_event_id)');
        $this->addSql('CREATE INDEX idx_support_reward_event_user_created ON support_reward_event (user_id, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE support_reward_event');
        $this->addSql('DROP TABLE support_campaign_history');
        $this->addSql('DROP TABLE support_campaign_state');
    }
}

