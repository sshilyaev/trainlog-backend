<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250314000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'TrainLog schema: profiles, coach_trainee_links, measurements, goals, connection_tokens, memberships, visits';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE profiles (
            id VARCHAR(36) NOT NULL,
            user_id VARCHAR(128) NOT NULL,
            type VARCHAR(20) NOT NULL,
            name VARCHAR(255) NOT NULL,
            gym_name VARCHAR(255) DEFAULT NULL,
            gender VARCHAR(20) DEFAULT NULL,
            icon_emoji VARCHAR(16) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_profiles_user_id ON profiles (user_id)');
        $this->addSql('CREATE INDEX idx_profiles_type ON profiles (type)');

        $this->addSql('CREATE TABLE coach_trainee_links (
            id VARCHAR(36) NOT NULL,
            coach_profile_id VARCHAR(36) NOT NULL,
            trainee_profile_id VARCHAR(36) NOT NULL,
            display_name VARCHAR(255) DEFAULT NULL,
            note TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_coach_trainee_links_coach ON coach_trainee_links (coach_profile_id)');
        $this->addSql('CREATE INDEX idx_coach_trainee_links_trainee ON coach_trainee_links (trainee_profile_id)');
        $this->addSql('CREATE UNIQUE INDEX uq_coach_trainee ON coach_trainee_links (coach_profile_id, trainee_profile_id)');
        $this->addSql('ALTER TABLE coach_trainee_links ADD CONSTRAINT FK_coach_trainee_coach FOREIGN KEY (coach_profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE coach_trainee_links ADD CONSTRAINT FK_coach_trainee_trainee FOREIGN KEY (trainee_profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE measurements (
            id VARCHAR(36) NOT NULL,
            profile_id VARCHAR(36) NOT NULL,
            date DATE NOT NULL,
            weight DOUBLE PRECISION DEFAULT NULL,
            height DOUBLE PRECISION DEFAULT NULL,
            neck DOUBLE PRECISION DEFAULT NULL,
            shoulders DOUBLE PRECISION DEFAULT NULL,
            left_biceps DOUBLE PRECISION DEFAULT NULL,
            right_biceps DOUBLE PRECISION DEFAULT NULL,
            waist DOUBLE PRECISION DEFAULT NULL,
            belly DOUBLE PRECISION DEFAULT NULL,
            left_thigh DOUBLE PRECISION DEFAULT NULL,
            right_thigh DOUBLE PRECISION DEFAULT NULL,
            hips DOUBLE PRECISION DEFAULT NULL,
            buttocks DOUBLE PRECISION DEFAULT NULL,
            left_calf DOUBLE PRECISION DEFAULT NULL,
            right_calf DOUBLE PRECISION DEFAULT NULL,
            note TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_measurements_profile_date ON measurements (profile_id, date)');
        $this->addSql('ALTER TABLE measurements ADD CONSTRAINT FK_measurements_profile FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE goals (
            id VARCHAR(36) NOT NULL,
            profile_id VARCHAR(36) NOT NULL,
            measurement_type VARCHAR(50) NOT NULL,
            target_value DOUBLE PRECISION NOT NULL,
            target_date DATE NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_goals_profile_target_date ON goals (profile_id, target_date)');
        $this->addSql('ALTER TABLE goals ADD CONSTRAINT FK_goals_profile FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE connection_tokens (
            id VARCHAR(36) NOT NULL,
            trainee_profile_id VARCHAR(36) NOT NULL,
            token VARCHAR(32) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            used BOOLEAN NOT NULL DEFAULT false,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_connection_tokens_token ON connection_tokens (token)');
        $this->addSql('CREATE INDEX idx_connection_tokens_trainee ON connection_tokens (trainee_profile_id)');
        $this->addSql('CREATE INDEX idx_connection_tokens_expires ON connection_tokens (expires_at)');
        $this->addSql('ALTER TABLE connection_tokens ADD CONSTRAINT FK_connection_tokens_trainee FOREIGN KEY (trainee_profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE memberships (
            id VARCHAR(36) NOT NULL,
            coach_profile_id VARCHAR(36) NOT NULL,
            trainee_profile_id VARCHAR(36) NOT NULL,
            total_sessions INT NOT NULL,
            used_sessions INT NOT NULL DEFAULT 0,
            price_rub INT DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            display_code VARCHAR(10) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_memberships_coach ON memberships (coach_profile_id)');
        $this->addSql('CREATE INDEX idx_memberships_trainee ON memberships (trainee_profile_id)');
        $this->addSql('CREATE INDEX idx_memberships_coach_trainee_status ON memberships (coach_profile_id, trainee_profile_id, status)');
        $this->addSql('ALTER TABLE memberships ADD CONSTRAINT FK_memberships_coach FOREIGN KEY (coach_profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE memberships ADD CONSTRAINT FK_memberships_trainee FOREIGN KEY (trainee_profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE visits (
            id VARCHAR(36) NOT NULL,
            coach_profile_id VARCHAR(36) NOT NULL,
            trainee_profile_id VARCHAR(36) NOT NULL,
            date DATE NOT NULL,
            status VARCHAR(20) NOT NULL,
            payment_status VARCHAR(20) NOT NULL,
            membership_id VARCHAR(36) DEFAULT NULL,
            membership_display_code VARCHAR(10) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_visits_coach_date ON visits (coach_profile_id, date)');
        $this->addSql('CREATE INDEX idx_visits_trainee ON visits (trainee_profile_id)');
        $this->addSql('CREATE INDEX idx_visits_membership ON visits (membership_id)');
        $this->addSql('ALTER TABLE visits ADD CONSTRAINT FK_visits_coach FOREIGN KEY (coach_profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE visits ADD CONSTRAINT FK_visits_trainee FOREIGN KEY (trainee_profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE visits ADD CONSTRAINT FK_visits_membership FOREIGN KEY (membership_id) REFERENCES memberships (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE coach_trainee_links DROP CONSTRAINT FK_coach_trainee_coach');
        $this->addSql('ALTER TABLE coach_trainee_links DROP CONSTRAINT FK_coach_trainee_trainee');
        $this->addSql('ALTER TABLE measurements DROP CONSTRAINT FK_measurements_profile');
        $this->addSql('ALTER TABLE goals DROP CONSTRAINT FK_goals_profile');
        $this->addSql('ALTER TABLE connection_tokens DROP CONSTRAINT FK_connection_tokens_trainee');
        $this->addSql('ALTER TABLE memberships DROP CONSTRAINT FK_memberships_coach');
        $this->addSql('ALTER TABLE memberships DROP CONSTRAINT FK_memberships_trainee');
        $this->addSql('ALTER TABLE visits DROP CONSTRAINT FK_visits_coach');
        $this->addSql('ALTER TABLE visits DROP CONSTRAINT FK_visits_trainee');
        $this->addSql('ALTER TABLE visits DROP CONSTRAINT FK_visits_membership');
        $this->addSql('DROP TABLE profiles');
        $this->addSql('DROP TABLE coach_trainee_links');
        $this->addSql('DROP TABLE measurements');
        $this->addSql('DROP TABLE goals');
        $this->addSql('DROP TABLE connection_tokens');
        $this->addSql('DROP TABLE memberships');
        $this->addSql('DROP TABLE visits');
    }
}
