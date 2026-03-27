<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326143000_localize_and_expand_record_activities_catalog extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Localize record activity subcategories and expand achievements catalog content';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE record_activities_catalog SET activity_type = 'силовые' WHERE slug IN (
            'bench-press','squat','deadlift','overhead-press','incline-bench-press','barbell-row','lat-pulldown','leg-press','hip-thrust','romanian-deadlift','bicep-curl','tricep-extension'
        )");
        $this->addSql("UPDATE record_activities_catalog SET activity_type = 'гимнастика' WHERE slug IN (
            'pull-ups','dips','push-ups'
        )");
        $this->addSql("UPDATE record_activities_catalog SET activity_type = 'выносливость' WHERE slug IN (
            'plank','jump-rope'
        )");
        $this->addSql("UPDATE record_activities_catalog SET activity_type = 'кардио' WHERE slug IN (
            'run','rowing-machine','cycling','swimming','walk'
        )");

        $this->addSql("INSERT INTO record_activities_catalog (id, slug, name, activity_type, default_metrics, display_order, is_active, created_at) VALUES
            ('ab340f31-c2f8-4d55-8e59-2da44dc39001', 'leg-extension', 'Разгибание ног в тренажере', 'силовые', '[\"weight\",\"reps\"]', 240, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39002', 'leg-curl', 'Сгибание ног в тренажере', 'силовые', '[\"weight\",\"reps\"]', 250, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39003', 'seated-row', 'Тяга горизонтального блока', 'силовые', '[\"weight\",\"reps\"]', 260, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39004', 'chest-fly', 'Сведения рук в тренажере', 'силовые', '[\"weight\",\"reps\"]', 270, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39005', 'calf-raise', 'Подъемы на икры', 'силовые', '[\"weight\",\"reps\"]', 280, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39006', 'front-squat', 'Фронтальный присед', 'силовые', '[\"weight\",\"reps\"]', 290, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39007', 'sumo-deadlift', 'Становая тяга сумо', 'силовые', '[\"weight\",\"reps\"]', 300, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39008', 'arnold-press', 'Жим Арнольда', 'силовые', '[\"weight\",\"reps\"]', 310, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39009', 'face-pull', 'Тяга к лицу', 'силовые', '[\"weight\",\"reps\"]', 320, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39010', 'bulgarian-split-squat', 'Болгарский сплит-присед', 'силовые', '[\"weight\",\"reps\"]', 330, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39011', 'muscle-up', 'Выход силой на перекладине', 'гимнастика', '[\"reps\"]', 340, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39012', 'toes-to-bar', 'Подъем ног к перекладине', 'гимнастика', '[\"reps\"]', 350, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39013', 'handstand-hold', 'Стойка на руках (удержание)', 'гимнастика', '[\"duration\"]', 360, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39014', 'burpees', 'Берпи', 'функциональные', '[\"reps\",\"duration\"]', 370, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39015', 'kettlebell-swing', 'Махи гирей', 'функциональные', '[\"weight\",\"reps\"]', 380, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39016', 'farmer-walk', 'Фермерская прогулка', 'функциональные', '[\"weight\",\"distance\",\"duration\"]', 390, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39017', 'box-jump', 'Прыжок на тумбу', 'взрывная_сила', '[\"reps\"]', 400, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39018', 'broad-jump', 'Прыжок в длину с места', 'взрывная_сила', '[\"distance\"]', 410, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39019', 'sprint-100m', 'Спринт 100 м', 'взрывная_сила', '[\"duration\",\"speed\"]', 420, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39020', 'elliptical', 'Эллиптический тренажер', 'кардио', '[\"duration\",\"distance\",\"speed\"]', 430, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39021', 'stair-climber', 'Степпер', 'кардио', '[\"duration\",\"distance\"]', 440, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39022', 'bike-ergometer', 'Велоэргометр', 'кардио', '[\"duration\",\"distance\",\"speed\"]', 450, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39023', 'run-5k', 'Бег 5 км', 'выносливость', '[\"duration\",\"speed\"]', 460, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39024', 'run-10k', 'Бег 10 км', 'выносливость', '[\"duration\",\"speed\"]', 470, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39025', 'rowing-2k', 'Гребля 2000 м', 'выносливость', '[\"duration\",\"speed\"]', 480, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39026', 'cossack-squat', 'Казачий присед', 'мобильность', '[\"reps\"]', 490, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39027', 'shoulder-dislocates', 'Плечевые протяжки', 'мобильность', '[\"reps\"]', 500, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39028', 'single-leg-stance', 'Баланс на одной ноге', 'баланс', '[\"duration\"]', 510, true, NOW()),
            ('ab340f31-c2f8-4d55-8e59-2da44dc39029', 'turkish-get-up', 'Турецкий подъем', 'баланс', '[\"weight\",\"reps\"]', 520, true, NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM record_activities_catalog WHERE slug IN (
            'leg-extension','leg-curl','seated-row','chest-fly','calf-raise','front-squat','sumo-deadlift','arnold-press','face-pull','bulgarian-split-squat',
            'muscle-up','toes-to-bar','handstand-hold','burpees','kettlebell-swing','farmer-walk',
            'box-jump','broad-jump','sprint-100m','elliptical','stair-climber','bike-ergometer',
            'run-5k','run-10k','rowing-2k','cossack-squat','shoulder-dislocates','single-leg-stance','turkish-get-up'
        )");

        $this->addSql("UPDATE record_activities_catalog SET activity_type = 'strength' WHERE slug IN (
            'bench-press','squat','deadlift','pull-ups','overhead-press','incline-bench-press','dips','push-ups','barbell-row','lat-pulldown','leg-press','lunges','hip-thrust','romanian-deadlift','bicep-curl','tricep-extension','plank'
        )");
        $this->addSql("UPDATE record_activities_catalog SET activity_type = 'cardio' WHERE slug IN (
            'run','jump-rope','rowing-machine','cycling','swimming','walk'
        )");
    }
}
