<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260320120000_create_calculators extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create calculators and calculator_definitions tables + seed calculator definitions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE calculators (
                id VARCHAR(50) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                sort_order INT NOT NULL,
                is_enabled BOOLEAN NOT NULL DEFAULT true,
                version INT NOT NULL DEFAULT 1,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                PRIMARY KEY(id)
            );
        ');

        $this->addSql('
            CREATE TABLE calculator_definitions (
                calculator_id VARCHAR(50) NOT NULL,
                definition JSONB NOT NULL,
                version INT NOT NULL DEFAULT 1,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                PRIMARY KEY(calculator_id)
            );
        ');

        $this->addSql('
            ALTER TABLE calculator_definitions
            ADD CONSTRAINT fk_calculator_definitions_calculators
            FOREIGN KEY (calculator_id) REFERENCES calculators (id)
            ON DELETE CASCADE;
        ');

        // --- Seed calculators ---
        $nowSql = 'NOW()';
        $this->addSql("INSERT INTO calculators (id, slug, title, description, sort_order, is_enabled, version, created_at, updated_at) VALUES ('body_fat', 'body_fat', 'Процент жира', 'US Navy (антропометрия)', 1, true, 1, {$nowSql}, {$nowSql})");
        $this->addSql("INSERT INTO calculators (id, slug, title, description, sort_order, is_enabled, version, created_at, updated_at) VALUES ('bench_1rm', 'bench_1rm', 'Жим лёжа 1RM', 'Одноповторный максимум и рабочие веса', 2, true, 1, {$nowSql}, {$nowSql})");
        $this->addSql("INSERT INTO calculators (id, slug, title, description, sort_order, is_enabled, version, created_at, updated_at) VALUES ('bju', 'bju', 'КБЖУ', 'Норма калорий и макросов', 3, true, 1, {$nowSql}, {$nowSql})");
        $this->addSql("INSERT INTO calculators (id, slug, title, description, sort_order, is_enabled, version, created_at, updated_at) VALUES ('bmi', 'bmi', 'ИМТ', 'Индекс массы тела и категория', 4, true, 1, {$nowSql}, {$nowSql})");
        $this->addSql("INSERT INTO calculators (id, slug, title, description, sort_order, is_enabled, version, created_at, updated_at) VALUES ('protein_norm', 'protein_norm', 'Норма белка', 'Сколько белка нужно в день', 5, true, 1, {$nowSql}, {$nowSql})");
        $this->addSql("INSERT INTO calculators (id, slug, title, description, sort_order, is_enabled, version, created_at, updated_at) VALUES ('water_balance', 'water_balance', 'Водный баланс', 'Суточная норма воды', 6, true, 1, {$nowSql}, {$nowSql})");

        // --- Seed definitions ---
        $bodyFat = json_encode([
            'calculatorId' => 'body_fat',
            'title' => 'Процент жира',
            'description' => 'US Navy (антропометрия)',
            'helpText' => 'Метод US Navy: измерения сантиметровой лентой, результат ориентировочный.',
            'ui' => [
                'groups' => [
                    ['title' => 'Параметры', 'inputKeys' => ['sex', 'heightCm']],
                    ['title' => 'Измерения', 'inputKeys' => ['neckCm', 'waistCm', 'hipsCm']],
                ],
            ],
            'inputs' => [
                [
                    'key' => 'sex',
                    'type' => 'select',
                    'title' => 'Пол',
                    'unit' => null,
                    'required' => true,
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'options' => [
                        ['value' => 'male', 'label' => 'Мужской'],
                        ['value' => 'female', 'label' => 'Женский'],
                    ],
                ],
                [
                    'key' => 'heightCm',
                    'type' => 'number',
                    'title' => 'Рост',
                    'unit' => 'см',
                    'required' => true,
                    'min' => 120,
                    'max' => 230,
                    'step' => 1,
                    'options' => [],
                ],
                [
                    'key' => 'neckCm',
                    'type' => 'number',
                    'title' => 'Шея',
                    'unit' => 'см',
                    'required' => true,
                    'min' => 10,
                    'max' => 60,
                    'step' => 0.1,
                    'options' => [],
                ],
                [
                    'key' => 'waistCm',
                    'type' => 'number',
                    'title' => 'Талия',
                    'unit' => 'см',
                    'required' => true,
                    'min' => 50,
                    'max' => 200,
                    'step' => 0.1,
                    'options' => [],
                ],
                [
                    'key' => 'hipsCm',
                    'type' => 'number',
                    'title' => 'Бёдра',
                    'unit' => 'см',
                    'required' => true,
                    'min' => 50,
                    'max' => 250,
                    'step' => 0.1,
                    'options' => [],
                ],
            ],
            'conditional' => [
                [
                    'if' => ['inputKey' => 'sex', 'equals' => 'female', 'notEquals' => null],
                    'showInputKeys' => ['hipsCm'],
                ],
            ],
            'outputs' => [
                [
                    'key' => 'bodyFatPercent',
                    'title' => 'Процент жира',
                    'unit' => '%',
                    'decimals' => 1,
                    'expression' => [
                        'type' => 'formula',
                        'value' => 'sex * (86.010 * log10(max(waistCm - neckCm, 0.000001)) - 70.041 * log10(max(heightCm, 0.000001)) + 36.76) + (1 - sex) * (163.205 * log10(max(waistCm + hipsCm - neckCm, 0.000001)) - 97.684 * log10(max(heightCm, 0.000001)) - 78.387)',
                    ],
                ],
            ],
            'interpretation' => [
                'targetOutputKey' => 'bodyFatPercent',
                'ranges' => [
                    ['min' => null, 'max' => 9.9, 'label' => 'Низкий/существенный', 'subtitle' => 'Результат зависит от пола и возраста.'],
                    ['min' => 10, 'max' => 24.9, 'label' => 'Средний диапазон', 'subtitle' => 'Дальнейшие шаги — питание + тренировки.'],
                    ['min' => 25, 'max' => null, 'label' => 'Повышенный', 'subtitle' => 'Нужна коррекция активности и калорий.'],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);

        $bench1Rm = json_encode([
            'calculatorId' => 'bench_1rm',
            'title' => 'Жим лёжа 1RM',
            'description' => 'Одноповторный максимум и рабочие веса',
            'helpText' => 'MVP: формула Epley и процентные диапазоны для рабочих подходов.',
            'ui' => [
                'groups' => [
                    ['title' => 'Тест', 'inputKeys' => ['weightKg', 'reps']],
                ],
            ],
            'inputs' => [
                [
                    'key' => 'weightKg',
                    'type' => 'number',
                    'title' => 'Вес на снаряде',
                    'unit' => 'кг',
                    'required' => true,
                    'min' => 5,
                    'max' => 400,
                    'step' => 0.5,
                    'options' => [],
                ],
                [
                    'key' => 'reps',
                    'type' => 'number',
                    'title' => 'Повторы',
                    'unit' => 'раз',
                    'required' => true,
                    'min' => 1,
                    'max' => 30,
                    'step' => 1,
                    'options' => [],
                ],
            ],
            'conditional' => [],
            'outputs' => [
                ['key' => 'oneRmKg', 'title' => '1RM', 'unit' => 'кг', 'decimals' => 1, 'expression' => ['type' => 'formula', 'value' => 'weightKg * (1 + reps / 30)']],
                ['key' => 'strengthMinKg', 'title' => 'Сила (min)', 'unit' => 'кг', 'decimals' => 1, 'expression' => ['type' => 'formula', 'value' => 'weightKg * (1 + reps / 30) * 0.85']],
                ['key' => 'strengthMaxKg', 'title' => 'Сила (max)', 'unit' => 'кг', 'decimals' => 1, 'expression' => ['type' => 'formula', 'value' => 'weightKg * (1 + reps / 30) * 0.90']],
                ['key' => 'hypertrophyMinKg', 'title' => 'Гипертрофия (min)', 'unit' => 'кг', 'decimals' => 1, 'expression' => ['type' => 'formula', 'value' => 'weightKg * (1 + reps / 30) * 0.70']],
                ['key' => 'hypertrophyMaxKg', 'title' => 'Гипертрофия (max)', 'unit' => 'кг', 'decimals' => 1, 'expression' => ['type' => 'formula', 'value' => 'weightKg * (1 + reps / 30) * 0.80']],
                ['key' => 'enduranceMinKg', 'title' => 'Выносливость (min)', 'unit' => 'кг', 'decimals' => 1, 'expression' => ['type' => 'formula', 'value' => 'weightKg * (1 + reps / 30) * 0.55']],
                ['key' => 'enduranceMaxKg', 'title' => 'Выносливость (max)', 'unit' => 'кг', 'decimals' => 1, 'expression' => ['type' => 'formula', 'value' => 'weightKg * (1 + reps / 30) * 0.65']],
            ],
            'interpretation' => null,
        ], JSON_UNESCAPED_UNICODE);

        $bju = json_encode([
            'calculatorId' => 'bju',
            'title' => 'КБЖУ',
            'description' => 'Норма калорий и макросов',
            'helpText' => 'MVP: BMR (Mifflin–St Jeor) → TDEE → корректировка по цели. Б/Ж фиксированы, У — остаток.',
            'ui' => [
                'groups' => [
                    ['title' => 'Параметры', 'inputKeys' => ['sex', 'age', 'heightCm', 'weightKg']],
                    ['title' => 'Активность и цель', 'inputKeys' => ['activity', 'goal']],
                ],
            ],
            'inputs' => [
                [
                    'key' => 'sex',
                    'type' => 'select',
                    'title' => 'Пол',
                    'unit' => null,
                    'required' => true,
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'options' => [
                        ['value' => 'male', 'label' => 'Мужской'],
                        ['value' => 'female', 'label' => 'Женский'],
                    ],
                ],
                [
                    'key' => 'age',
                    'type' => 'number',
                    'title' => 'Возраст',
                    'unit' => 'лет',
                    'required' => true,
                    'min' => 10,
                    'max' => 90,
                    'step' => 1,
                    'options' => [],
                ],
                [
                    'key' => 'heightCm',
                    'type' => 'number',
                    'title' => 'Рост',
                    'unit' => 'см',
                    'required' => true,
                    'min' => 120,
                    'max' => 220,
                    'step' => 1,
                    'options' => [],
                ],
                [
                    'key' => 'weightKg',
                    'type' => 'number',
                    'title' => 'Вес',
                    'unit' => 'кг',
                    'required' => true,
                    'min' => 30,
                    'max' => 250,
                    'step' => 0.1,
                    'options' => [],
                ],
                [
                    'key' => 'activity',
                    'type' => 'select',
                    'title' => 'Активность',
                    'unit' => null,
                    'required' => true,
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'options' => [
                        ['value' => 'low', 'label' => 'Минимальная'],
                        ['value' => 'light', 'label' => 'Лёгкая'],
                        ['value' => 'moderate', 'label' => 'Умеренная'],
                        ['value' => 'high', 'label' => 'Высокая'],
                        ['value' => 'extreme', 'label' => 'Экстремальная'],
                    ],
                ],
                [
                    'key' => 'goal',
                    'type' => 'select',
                    'title' => 'Цель',
                    'unit' => null,
                    'required' => true,
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'options' => [
                        ['value' => 'deficit', 'label' => 'Похудение'],
                        ['value' => 'maintain', 'label' => 'Поддержание'],
                        ['value' => 'surplus', 'label' => 'Набор массы'],
                    ],
                ],
            ],
            'conditional' => [],
            'outputs' => [
                [
                    'key' => 'caloriesKcal',
                    'title' => 'Калории',
                    'unit' => 'ккал/день',
                    'decimals' => 0,
                    'expression' => [
                        'type' => 'formula',
                        'value' => '(10 * weightKg + 6.25 * heightCm - 5 * age + sex) * activity * goal',
                    ],
                ],
                [
                    'key' => 'proteinGrams',
                    'title' => 'Белки',
                    'unit' => 'г/день',
                    'decimals' => 0,
                    'expression' => ['type' => 'formula', 'value' => '1.8 * weightKg'],
                ],
                [
                    'key' => 'fatGrams',
                    'title' => 'Жиры',
                    'unit' => 'г/день',
                    'decimals' => 0,
                    'expression' => ['type' => 'formula', 'value' => '0.9 * weightKg'],
                ],
                [
                    'key' => 'carbsGrams',
                    'title' => 'Углеводы',
                    'unit' => 'г/день',
                    'decimals' => 0,
                    'expression' => [
                        'type' => 'formula',
                        'value' => 'max(0, ((10 * weightKg + 6.25 * heightCm - 5 * age + sex) * activity * goal) - (1.8 * weightKg * 4 + 0.9 * weightKg * 9)) / 4',
                    ],
                ],
            ],
            'interpretation' => null,
        ], JSON_UNESCAPED_UNICODE);

        $bmi = json_encode([
            'calculatorId' => 'bmi',
            'title' => 'ИМТ',
            'description' => 'Индекс массы тела и категория',
            'helpText' => 'ИМТ помогает ориентировочно оценить вес относительно роста.',
            'ui' => [
                'groups' => [
                    ['title' => 'Основные', 'inputKeys' => ['heightCm', 'weightKg']],
                ],
            ],
            'inputs' => [
                [
                    'key' => 'heightCm',
                    'type' => 'number',
                    'title' => 'Рост',
                    'unit' => 'см',
                    'required' => true,
                    'min' => 80,
                    'max' => 250,
                    'step' => 1,
                    'options' => [],
                ],
                [
                    'key' => 'weightKg',
                    'type' => 'number',
                    'title' => 'Вес',
                    'unit' => 'кг',
                    'required' => true,
                    'min' => 30,
                    'max' => 250,
                    'step' => 0.1,
                    'options' => [],
                ],
            ],
            'conditional' => [],
            'outputs' => [
                [
                    'key' => 'bmi',
                    'title' => 'ИМТ',
                    'unit' => 'кг/м²',
                    'decimals' => 1,
                    'expression' => [
                        'type' => 'formula',
                        'value' => 'weightKg / ((heightCm / 100) ^ 2)',
                    ],
                ],
            ],
            'interpretation' => [
                'targetOutputKey' => 'bmi',
                'ranges' => [
                    ['min' => null, 'max' => 18.5, 'label' => 'Дефицит', 'subtitle' => 'Проверьте питание и восстановление.'],
                    ['min' => 18.5, 'max' => 24.9, 'label' => 'Норма', 'subtitle' => 'В пределах нормы.'],
                    ['min' => 25, 'max' => 29.9, 'label' => 'Избыток', 'subtitle' => 'Может быть полезна корректировка питания и активности.'],
                    ['min' => 30, 'max' => null, 'label' => 'Ожирение', 'subtitle' => 'Рекомендуется консультация специалиста.'],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);

        $proteinNorm = json_encode([
            'calculatorId' => 'protein_norm',
            'title' => 'Норма белка',
            'description' => 'Сколько белка нужно в день',
            'helpText' => 'Расчёт нормы белка на основе цели и массы тела.',
            'ui' => [
                'groups' => [
                    ['title' => 'Расчёт', 'inputKeys' => ['weightKg', 'goal']],
                ],
            ],
            'inputs' => [
                [
                    'key' => 'weightKg',
                    'type' => 'number',
                    'title' => 'Вес',
                    'unit' => 'кг',
                    'required' => true,
                    'min' => 30,
                    'max' => 250,
                    'step' => 0.1,
                    'options' => [],
                ],
                [
                    'key' => 'goal',
                    'type' => 'select',
                    'title' => 'Цель',
                    'unit' => null,
                    'required' => true,
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'options' => [
                        ['value' => 'deficit', 'label' => 'Похудение'],
                        ['value' => 'maintain', 'label' => 'Поддержание'],
                        ['value' => 'gain', 'label' => 'Набор массы'],
                    ],
                ],
            ],
            'conditional' => [],
            'outputs' => [
                [
                    'key' => 'proteinGrams',
                    'title' => 'Белок',
                    'unit' => 'г/день',
                    'decimals' => 0,
                    'expression' => ['type' => 'formula', 'value' => 'weightKg * goal'],
                ],
            ],
            'interpretation' => null,
        ], JSON_UNESCAPED_UNICODE);

        $waterBalance = json_encode([
            'calculatorId' => 'water_balance',
            'title' => 'Водный баланс',
            'description' => 'Суточная норма воды',
            'helpText' => 'MVP: 30 мл/кг + надбавка по активности.',
            'ui' => [
                'groups' => [
                    ['title' => 'Вводные данные', 'inputKeys' => ['weightKg', 'activity']],
                ],
            ],
            'inputs' => [
                [
                    'key' => 'weightKg',
                    'type' => 'number',
                    'title' => 'Вес',
                    'unit' => 'кг',
                    'required' => true,
                    'min' => 30,
                    'max' => 250,
                    'step' => 0.1,
                    'options' => [],
                ],
                [
                    'key' => 'activity',
                    'type' => 'select',
                    'title' => 'Активность',
                    'unit' => null,
                    'required' => true,
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'options' => [
                        ['value' => 'low', 'label' => 'Нет/низкая'],
                        ['value' => 'moderate', 'label' => 'Умеренная'],
                        ['value' => 'high', 'label' => 'Высокая'],
                    ],
                ],
            ],
            'conditional' => [],
            'outputs' => [
                [
                    'key' => 'waterLiters',
                    'title' => 'Норма воды',
                    'unit' => 'л/день',
                    'decimals' => 2,
                    'expression' => ['type' => 'formula', 'value' => '(weightKg * 30 + activity) / 1000'],
                ],
            ],
            'interpretation' => null,
        ], JSON_UNESCAPED_UNICODE);

        $this->addSql("INSERT INTO calculator_definitions (calculator_id, definition, version, updated_at) VALUES ('body_fat', '{$bodyFat}'::jsonb, 1, {$nowSql})");
        $this->addSql("INSERT INTO calculator_definitions (calculator_id, definition, version, updated_at) VALUES ('bench_1rm', '{$bench1Rm}'::jsonb, 1, {$nowSql})");
        $this->addSql("INSERT INTO calculator_definitions (calculator_id, definition, version, updated_at) VALUES ('bju', '{$bju}'::jsonb, 1, {$nowSql})");
        $this->addSql("INSERT INTO calculator_definitions (calculator_id, definition, version, updated_at) VALUES ('bmi', '{$bmi}'::jsonb, 1, {$nowSql})");
        $this->addSql("INSERT INTO calculator_definitions (calculator_id, definition, version, updated_at) VALUES ('protein_norm', '{$proteinNorm}'::jsonb, 1, {$nowSql})");
        $this->addSql("INSERT INTO calculator_definitions (calculator_id, definition, version, updated_at) VALUES ('water_balance', '{$waterBalance}'::jsonb, 1, {$nowSql})");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS calculator_definitions');
        $this->addSql('DROP TABLE IF EXISTS calculators');
    }
}

