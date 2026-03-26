<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326120000_expand_record_activities_catalog extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand record activities catalog (more exercises)';
    }

    public function up(Schema $schema): void
    {
        // Added exercises. Keep display_order gaps for future inserts.
        $this->addSql("INSERT INTO record_activities_catalog (id, slug, name, activity_type, default_metrics, display_order, is_active, created_at) VALUES
            ('0a9b0b65-1a21-4b4d-8b86-2e95aa49001a', 'overhead-press', 'Жим стоя', 'strength', '[\"weight\",\"reps\"]', 70, true, NOW()),
            ('1cb4f6e1-4775-4b18-8ac9-3c9b4d9a7b11', 'incline-bench-press', 'Жим на наклонной скамье', 'strength', '[\"weight\",\"reps\"]', 80, true, NOW()),
            ('2c5c2cc3-2f63-4d4c-8de7-695cb8a8f3b2', 'dips', 'Отжимания на брусьях', 'strength', '[\"reps\"]', 90, true, NOW()),
            ('3f9c2a35-7e6a-4d18-9d21-2c9b2f52d0b6', 'push-ups', 'Отжимания', 'strength', '[\"reps\"]', 100, true, NOW()),
            ('4b2a3f1a-5b41-45a3-8c43-27a7b7c2e1f3', 'barbell-row', 'Тяга штанги в наклоне', 'strength', '[\"weight\",\"reps\"]', 110, true, NOW()),
            ('5d6a7b8c-9e10-4f11-8a12-3b4c5d6e7f80', 'lat-pulldown', 'Тяга верхнего блока', 'strength', '[\"weight\",\"reps\"]', 120, true, NOW()),
            ('6e7f8012-3456-4abc-9def-0123456789ab', 'leg-press', 'Жим ногами', 'strength', '[\"weight\",\"reps\"]', 130, true, NOW()),
            ('7f8a9b0c-1d2e-4f30-9a40-5b6c7d8e9f00', 'lunges', 'Выпады', 'strength', '[\"weight\",\"reps\"]', 140, true, NOW()),
            ('8a9b0c1d-2e3f-4012-9a34-5b6c7d8e9f10', 'hip-thrust', 'Ягодичный мост (hip thrust)', 'strength', '[\"weight\",\"reps\"]', 150, true, NOW()),
            ('9b0c1d2e-3f40-4123-9a45-5b6c7d8e9f20', 'romanian-deadlift', 'Румынская тяга', 'strength', '[\"weight\",\"reps\"]', 160, true, NOW()),
            ('a0b1c2d3-e4f5-4012-9a34-5b6c7d8e9f30', 'bicep-curl', 'Сгибания на бицепс', 'strength', '[\"weight\",\"reps\"]', 170, true, NOW()),
            ('b1c2d3e4-f5a6-4123-9a45-5b6c7d8e9f40', 'tricep-extension', 'Разгибания на трицепс', 'strength', '[\"weight\",\"reps\"]', 180, true, NOW()),
            ('c2d3e4f5-a6b7-4234-9a56-5b6c7d8e9f50', 'plank', 'Планка', 'strength', '[\"duration\"]', 190, true, NOW()),
            ('d3e4f5a6-b7c8-4345-9a67-5b6c7d8e9f60', 'rowing-machine', 'Гребной тренажёр', 'cardio', '[\"duration\",\"distance\",\"speed\"]', 200, true, NOW()),
            ('e4f5a6b7-c8d9-4456-9a78-5b6c7d8e9f70', 'cycling', 'Велосипед', 'cardio', '[\"duration\",\"distance\",\"speed\"]', 210, true, NOW()),
            ('f5a6b7c8-d9e0-4567-9a89-5b6c7d8e9f80', 'swimming', 'Плавание', 'cardio', '[\"duration\",\"distance\"]', 220, true, NOW()),
            ('01234567-89ab-4cde-9f01-23456789abcd', 'walk', 'Ходьба', 'cardio', '[\"duration\",\"distance\",\"speed\"]', 230, true, NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM record_activities_catalog WHERE slug IN (
            'overhead-press',
            'incline-bench-press',
            'dips',
            'push-ups',
            'barbell-row',
            'lat-pulldown',
            'leg-press',
            'lunges',
            'hip-thrust',
            'romanian-deadlift',
            'bicep-curl',
            'tricep-extension',
            'plank',
            'rowing-machine',
            'cycling',
            'swimming',
            'walk'
        )");
    }
}

