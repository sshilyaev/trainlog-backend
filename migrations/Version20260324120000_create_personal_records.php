<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324120000_create_personal_records extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create personal records tables and activities catalog';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE record_activities_catalog (
            id VARCHAR(36) NOT NULL,
            slug VARCHAR(80) NOT NULL,
            name VARCHAR(255) NOT NULL,
            activity_type VARCHAR(100) DEFAULT NULL,
            default_metrics JSON NOT NULL,
            display_order SMALLINT NOT NULL,
            is_active BOOLEAN NOT NULL DEFAULT true,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uq_record_activities_catalog_slug ON record_activities_catalog (slug)');
        $this->addSql('CREATE INDEX idx_record_activities_active_order ON record_activities_catalog (is_active, display_order)');

        $this->addSql('CREATE TABLE personal_records (
            id VARCHAR(36) NOT NULL,
            profile_id VARCHAR(36) NOT NULL,
            created_by_profile_id VARCHAR(36) NOT NULL,
            record_date DATE NOT NULL,
            source_type VARCHAR(20) NOT NULL,
            activity_name VARCHAR(255) NOT NULL,
            activity_type VARCHAR(255) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_personal_records_profile_date ON personal_records (profile_id, record_date)');
        $this->addSql('CREATE INDEX idx_personal_records_profile_activity_name ON personal_records (profile_id, activity_name)');
        $this->addSql('ALTER TABLE personal_records ADD CONSTRAINT FK_personal_records_profile FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE personal_records ADD CONSTRAINT FK_personal_records_created_by_profile FOREIGN KEY (created_by_profile_id) REFERENCES profiles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE personal_record_metrics (
            id VARCHAR(36) NOT NULL,
            record_id VARCHAR(36) NOT NULL,
            metric_type VARCHAR(30) NOT NULL,
            value DOUBLE PRECISION NOT NULL,
            unit VARCHAR(20) NOT NULL,
            display_order SMALLINT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_personal_record_metrics_record ON personal_record_metrics (record_id)');
        $this->addSql('ALTER TABLE personal_record_metrics ADD CONSTRAINT FK_personal_record_metrics_record FOREIGN KEY (record_id) REFERENCES personal_records (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("INSERT INTO record_activities_catalog (id, slug, name, activity_type, default_metrics, display_order, is_active, created_at) VALUES
            ('f63839b0-6fce-4ae4-ad32-336cd13f6e2e', 'bench-press', 'Жим лежа', 'strength', '[\"weight\",\"reps\"]', 10, true, NOW()),
            ('35fd2807-f376-4ebe-bead-ef12b0cb25db', 'squat', 'Присед', 'strength', '[\"weight\",\"reps\"]', 20, true, NOW()),
            ('6e14f0fe-cfac-406d-8753-cd2ddfe7f330', 'deadlift', 'Становая тяга', 'strength', '[\"weight\",\"reps\"]', 30, true, NOW()),
            ('5e50ea11-542d-47fa-be74-9f4f3a8aad08', 'pull-ups', 'Подтягивания', 'strength', '[\"reps\"]', 40, true, NOW()),
            ('f03f4a00-b44d-4e10-bdb0-8936ba98d84a', 'run', 'Бег', 'cardio', '[\"distance\",\"duration\",\"speed\"]', 50, true, NOW()),
            ('ed6f7c89-8ca4-47a4-a7a1-1e497fcb2f80', 'jump-rope', 'Скакалка', 'cardio', '[\"duration\",\"reps\"]', 60, true, NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personal_record_metrics DROP CONSTRAINT FK_personal_record_metrics_record');
        $this->addSql('ALTER TABLE personal_records DROP CONSTRAINT FK_personal_records_profile');
        $this->addSql('ALTER TABLE personal_records DROP CONSTRAINT FK_personal_records_created_by_profile');
        $this->addSql('DROP TABLE personal_record_metrics');
        $this->addSql('DROP TABLE personal_records');
        $this->addSql('DROP TABLE record_activities_catalog');
    }
}
