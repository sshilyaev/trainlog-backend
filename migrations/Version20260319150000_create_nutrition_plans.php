<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260319150000_create_nutrition_plans extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create nutrition_plans table for coach-trainee nutrition settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE nutrition_plans (id VARCHAR(36) NOT NULL, coach_profile_id VARCHAR(36) NOT NULL, trainee_profile_id VARCHAR(36) NOT NULL, weight_kg_used DOUBLE PRECISION NOT NULL, protein_per_kg DOUBLE PRECISION NOT NULL, fat_per_kg DOUBLE PRECISION NOT NULL, carbs_per_kg DOUBLE PRECISION NOT NULL, protein_grams DOUBLE PRECISION NOT NULL, fat_grams DOUBLE PRECISION NOT NULL, carbs_grams DOUBLE PRECISION NOT NULL, calories INT NOT NULL, comment TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_nutrition_plans_coach ON nutrition_plans (coach_profile_id)');
        $this->addSql('CREATE INDEX idx_nutrition_plans_trainee ON nutrition_plans (trainee_profile_id)');
        $this->addSql('CREATE UNIQUE INDEX uq_nutrition_plans_coach_trainee ON nutrition_plans (coach_profile_id, trainee_profile_id)');
        $this->addSql('ALTER TABLE nutrition_plans ADD CONSTRAINT FK_22E2E95A99B8F093 FOREIGN KEY (coach_profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE nutrition_plans ADD CONSTRAINT FK_22E2E95A35CCB6F3 FOREIGN KEY (trainee_profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE nutrition_plans DROP CONSTRAINT FK_22E2E95A99B8F093');
        $this->addSql('ALTER TABLE nutrition_plans DROP CONSTRAINT FK_22E2E95A35CCB6F3');
        $this->addSql('DROP TABLE nutrition_plans');
    }
}

