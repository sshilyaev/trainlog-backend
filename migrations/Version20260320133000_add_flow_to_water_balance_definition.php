<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260320133000_add_flow_to_water_balance_definition extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional multistep flow to water_balance calculator definition';
    }

    public function up(Schema $schema): void
    {
        $definition = json_encode([
            'calculatorId' => 'water_balance',
            'title' => 'Водный баланс',
            'description' => 'Суточная норма воды',
            'helpText' => 'Расчет учитывает вес, активность, тренировки, климат и повседневные факторы.',
            'ui' => [
                'groups' => [
                    ['title' => 'Личные параметры', 'inputKeys' => ['sex', 'age', 'weightKg', 'heightCm', 'state']],
                    ['title' => 'Образ жизни', 'inputKeys' => ['activityLevel', 'workType', 'coffeeCups', 'alcoholPortions', 'trainingMinutesPerDay']],
                    ['title' => 'Условия среды', 'inputKeys' => ['temperatureBand', 'humidityBand', 'extraConditions']],
                ],
            ],
            'flow' => [
                'mode' => 'multistep',
                'steps' => [
                    [
                        'id' => 'params',
                        'title' => 'Личные параметры',
                        'subtitle' => 'Основные данные для расчёта потребности в воде',
                        'inputKeys' => ['sex', 'age', 'weightKg', 'heightCm', 'state'],
                        'nextButtonTitle' => 'Далее',
                    ],
                    [
                        'id' => 'lifestyle',
                        'title' => 'Образ жизни',
                        'subtitle' => 'Активность и повседневные условия',
                        'inputKeys' => ['activityLevel', 'workType', 'coffeeCups', 'alcoholPortions', 'trainingMinutesPerDay'],
                        'nextButtonTitle' => 'Далее',
                    ],
                    [
                        'id' => 'environment',
                        'title' => 'Условия среды',
                        'subtitle' => 'Температура, влажность и дополнительные факторы',
                        'inputKeys' => ['temperatureBand', 'humidityBand', 'extraConditions'],
                        'nextButtonTitle' => 'Рассчитать',
                    ],
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
                    'key' => 'state',
                    'type' => 'select',
                    'title' => 'Состояние',
                    'unit' => null,
                    'required' => true,
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'options' => [
                        ['value' => 'normal', 'label' => 'Обычное'],
                        ['value' => 'pregnancy', 'label' => 'Беременность'],
                        ['value' => 'lactation', 'label' => 'Лактация'],
                        ['value' => 'illness', 'label' => 'Болезнь/лихорадка'],
                    ],
                ],
                [
                    'key' => 'activityLevel',
                    'type' => 'select',
                    'title' => 'Общая активность',
                    'unit' => null,
                    'required' => true,
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'options' => [
                        ['value' => 'low', 'label' => 'Низкая'],
                        ['value' => 'moderate', 'label' => 'Умеренная'],
                        ['value' => 'high', 'label' => 'Высокая'],
                    ],
                ],
                [
                    'key' => 'workType',
                    'type' => 'select',
                    'title' => 'Тип работы',
                    'unit' => null,
                    'required' => true,
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'options' => [
                        ['value' => 'office', 'label' => 'Офис/сидячая'],
                        ['value' => 'mixed', 'label' => 'Смешанная'],
                        ['value' => 'physical', 'label' => 'Физическая'],
                    ],
                ],
                [
                    'key' => 'coffeeCups',
                    'type' => 'number',
                    'title' => 'Кофе (чашек)',
                    'unit' => 'чашки/день',
                    'required' => true,
                    'min' => 0,
                    'max' => 10,
                    'step' => 1,
                    'options' => [],
                ],
                [
                    'key' => 'alcoholPortions',
                    'type' => 'number',
                    'title' => 'Алкоголь (порций)',
                    'unit' => 'порции/день',
                    'required' => true,
                    'min' => 0,
                    'max' => 10,
                    'step' => 1,
                    'options' => [],
                ],
                [
                    'key' => 'trainingMinutesPerDay',
                    'type' => 'number',
                    'title' => 'Тренировки',
                    'unit' => 'мин/день',
                    'required' => true,
                    'min' => 0,
                    'max' => 300,
                    'step' => 5,
                    'options' => [],
                ],
                [
                    'key' => 'temperatureBand',
                    'type' => 'select',
                    'title' => 'Температура',
                    'unit' => null,
                    'required' => true,
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'options' => [
                        ['value' => 'cool', 'label' => 'Прохладно'],
                        ['value' => 'warm', 'label' => 'Тепло'],
                        ['value' => 'hot', 'label' => 'Жарко'],
                    ],
                ],
                [
                    'key' => 'humidityBand',
                    'type' => 'select',
                    'title' => 'Влажность',
                    'unit' => null,
                    'required' => true,
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'options' => [
                        ['value' => 'low', 'label' => 'Низкая'],
                        ['value' => 'medium', 'label' => 'Средняя'],
                        ['value' => 'high', 'label' => 'Высокая'],
                    ],
                ],
                [
                    'key' => 'extraConditions',
                    'type' => 'select',
                    'title' => 'Доп. условия',
                    'unit' => null,
                    'required' => true,
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'options' => [
                        ['value' => 'none', 'label' => 'Нет'],
                        ['value' => 'air_travel', 'label' => 'Перелет/пересушенный воздух'],
                        ['value' => 'sauna', 'label' => 'Сауна/сильное потоотделение'],
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
                    'expression' => [
                        'type' => 'formula',
                        // Practical hydration model:
                        // base by body weight + physiologic/lifestyle/environment adjustments (ml/day)
                        'value' => 'max(0, (weightKg * 30 + sex + max(0, (heightCm - 175) * 1.5) - max(0, age - 55) * 2 + state + activityLevel + workType + coffeeCups * 150 + alcoholPortions * 250 + trainingMinutesPerDay * 12 + temperatureBand + humidityBand + extraConditions) / 1000)',
                    ],
                ],
            ],
            'interpretation' => null,
        ], JSON_UNESCAPED_UNICODE);

        if (!\is_string($definition)) {
            throw new \RuntimeException('Failed to encode water_balance definition JSON');
        }

        $this->addSql(
            "UPDATE calculator_definitions
             SET definition = '{$definition}'::jsonb,
                 version = version + 1,
                 updated_at = NOW()
             WHERE calculator_id = 'water_balance'"
        );

        $this->addSql(
            "UPDATE calculators
             SET version = version + 1,
                 updated_at = NOW()
             WHERE id = 'water_balance'"
        );
    }

    public function down(Schema $schema): void
    {
        $definition = json_encode([
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

        if (!\is_string($definition)) {
            throw new \RuntimeException('Failed to encode water_balance definition JSON');
        }

        $this->addSql(
            "UPDATE calculator_definitions
             SET definition = '{$definition}'::jsonb,
                 version = GREATEST(1, version - 1),
                 updated_at = NOW()
             WHERE calculator_id = 'water_balance'"
        );

        $this->addSql(
            "UPDATE calculators
             SET version = GREATEST(1, version - 1),
                 updated_at = NOW()
             WHERE id = 'water_balance'"
        );
    }
}

