<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Api\ApiException;
use App\Entity\Calculator;
use App\Enum\ApiError;
use App\Repository\CalculatorDefinitionRepository;
use App\Repository\CalculatorRepository;
use App\Service\Api\Calculators\SafeExpressionEvaluator;
use RuntimeException;

final class CalculatorsAppService
{
    public function __construct(
        private readonly CalculatorRepository $calculatorRepository,
        private readonly CalculatorDefinitionRepository $calculatorDefinitionRepository,
        private readonly SafeExpressionEvaluator $expressionEvaluator,
    ) {
    }

    /**
     * @return array{calculators: list<array<string, mixed>>}
     */
    public function catalog(string $userId): array
    {
        $calculators = $this->calculatorRepository->findEnabledOrdered();

        return [
            'calculators' => array_map(
                static fn (Calculator $c) => [
                    'id' => $c->getId(),
                    'title' => $c->getTitle(),
                    'description' => $c->getDescription(),
                    'order' => $c->getOrder(),
                    'isEnabled' => $c->isEnabled(),
                    'version' => $c->getVersion(),
                ],
                $calculators,
            ),
        ];
    }

    /**
     * @return array{definition: array<string, mixed>}
     */
    public function definition(string $calculatorId): array
    {
        $calculator = $this->calculatorRepository->findOneById($calculatorId);
        if ($calculator === null || !$calculator->isEnabled()) {
            throw new ApiException(ApiError::CalculatorNotFound);
        }

        $def = $this->calculatorDefinitionRepository->findOneByCalculatorId($calculatorId);
        if ($def === null) {
            throw new ApiException(ApiError::CalculatorNotFound);
        }

        $definition = $def->getDefinition();
        $this->validateFlowContract($definition);

        return ['definition' => $definition];
    }

    /**
     * @param array<string, mixed> $inputs
     * @return array{
     *   calculatorId: string,
     *   outputs: array<string, float>,
     *   interpretation: array{label: string, subtitle: string}|null,
     *   summary: string|null,
     *   resultDescriptions?: list<array{title:string,description:string}>,
     *   recommendations?: list<array{title:string,description:string}>
     * }
     */
    public function calculate(string $calculatorId, ?string $profileId, array $inputs): array
    {
        $calculator = $this->calculatorRepository->findOneById($calculatorId);
        if ($calculator === null || !$calculator->isEnabled()) {
            throw new ApiException(ApiError::CalculatorNotFound);
        }

        $def = $this->calculatorDefinitionRepository->findOneByCalculatorId($calculatorId);
        if ($def === null) {
            throw new ApiException(ApiError::CalculatorNotFound);
        }

        $definition = $def->getDefinition();
        $definitionInputs = $definition['inputs'] ?? null;
        $definitionOutputs = $definition['outputs'] ?? null;
        if (!\is_array($definitionInputs) || !\is_array($definitionOutputs)) {
            throw new ApiException(ApiError::CalculatorInvalidDefinition);
        }

        $inputDefsByKey = [];
        foreach ($definitionInputs as $inp) {
            if (!\is_array($inp) || !isset($inp['key'], $inp['type'])) {
                throw new ApiException(ApiError::CalculatorInvalidDefinition);
            }
            $inputDefsByKey[(string) $inp['key']] = $inp;
        }

        $allowedVariables = \array_keys($inputDefsByKey);
        $visibleMap = $this->computeVisibleInputs($definition, $inputs, $inputDefsByKey);

        $this->validateInputs($calculatorId, $definitionInputs, $inputs, $visibleMap);

        // Numeric env for expression evaluation (select values are mapped to numeric factors).
        $env = $this->buildNumericEnv($calculatorId, $definitionInputs, $inputs, $visibleMap);

        $outputs = [];
        foreach ($definitionOutputs as $outDef) {
            if (!\is_array($outDef) || !isset($outDef['key'])) {
                throw new ApiException(ApiError::CalculatorInvalidDefinition);
            }
            $outputKey = (string) $outDef['key'];

            $expr = $outDef['expression'] ?? null;
            if (!\is_array($expr) || !isset($expr['value']) || !\is_string($expr['value']) || $expr['value'] === '') {
                throw new ApiException(ApiError::CalculatorInvalidDefinition);
            }

            $value = $this->evaluateExpressionSafely(
                expression: (string) $expr['value'],
                variables: $env,
                allowedVariables: $allowedVariables,
            );
            $valueRounded = $this->roundOutputValue($calculatorId, $outputKey, $value);
            $outputs[$outputKey] = $valueRounded;
        }

        $interpretation = $this->computeInterpretation($calculatorId, $inputs, $outputs, $visibleMap);
        $summary = $this->computeSummary($calculatorId, $outputs, $interpretation);
        $resultDescriptions = $this->computeResultDescriptions($calculatorId, $outputs, $interpretation);
        $recommendations = $this->computeRecommendations($calculatorId, $outputs, $interpretation);

        $response = [
            'calculatorId' => $calculator->getId(),
            'outputs' => $outputs,
            'interpretation' => $interpretation,
            'summary' => $summary,
        ];

        if ($resultDescriptions !== []) {
            $response['resultDescriptions'] = $resultDescriptions;
        }
        if ($recommendations !== []) {
            $response['recommendations'] = $recommendations;
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $inputs
     * @param array<string, array<string, mixed>> $inputDefsByKey
     * @return array<string, bool>
     */
    private function computeVisibleInputs(array $definition, array $inputs, array $inputDefsByKey): array
    {
        $conditional = $definition['conditional'] ?? [];
        if ($conditional === null) {
            $conditional = [];
        }

        $conditionalRules = \is_array($conditional) ? $conditional : [];
        $visibleMap = [];

        foreach ($inputDefsByKey as $key => $_def) {
            $rulesForKey = [];
            foreach ($conditionalRules as $rule) {
                if (!\is_array($rule) || !isset($rule['showInputKeys'], $rule['if']) || !\is_array($rule['showInputKeys'])) {
                    continue;
                }
                if (\in_array($key, $rule['showInputKeys'], true)) {
                    $rulesForKey[] = $rule;
                }
            }

            if ($rulesForKey === []) {
                $visibleMap[$key] = true;
                continue;
            }

            $visible = false;
            foreach ($rulesForKey as $rule) {
                $if = $rule['if'] ?? null;
                if (!\is_array($if) || !isset($if['inputKey'])) {
                    continue;
                }
                $controllingKey = (string) $if['inputKey'];
                $controllingValue = $inputs[$controllingKey] ?? '';
                if (\is_int($controllingValue) || \is_float($controllingValue)) {
                    $controllingValue = (string) $controllingValue;
                }
                if (!\is_string($controllingValue)) {
                    $controllingValue = (string) $controllingValue;
                }

                $equals = $if['equals'] ?? null;
                $notEquals = $if['notEquals'] ?? null;

                if ($equals !== null) {
                    if ($controllingValue === (string) $equals) {
                        $visible = true;
                        break;
                    }
                    continue;
                }
                if ($notEquals !== null) {
                    if ($controllingValue !== (string) $notEquals) {
                        $visible = true;
                        break;
                    }
                    continue;
                }
            }

            $visibleMap[$key] = $visible;
        }

        return $visibleMap;
    }

    /**
     * @param array<int, array<string, mixed>> $definitionInputs
     * @param array<string, mixed> $inputs
     * @param array<string, bool> $visibleMap
     */
    private function validateInputs(string $calculatorId, array $definitionInputs, array $inputs, array $visibleMap): void
    {
        // Definition-level validation (type + range + select options).
        foreach ($definitionInputs as $inputDef) {
            if (!\is_array($inputDef) || !isset($inputDef['key'], $inputDef['type'])) {
                continue;
            }

            $key = (string) $inputDef['key'];
            $type = (string) $inputDef['type']; // number|select
            $required = isset($inputDef['required']) ? (bool) $inputDef['required'] : false;
            $visible = $visibleMap[$key] ?? true;

            if ($required && $visible) {
                if (!\array_key_exists($key, $inputs)) {
                    throw new ApiException(
                        ApiError::CalculatorRequiredFieldMissing,
                        null,
                        ['messages' => ["Поле {$key} обязательно для расчёта."]],
                    );
                }
            }

            if (!\array_key_exists($key, $inputs)) {
                continue; // not provided and either not required or not visible
            }

            $rawValue = $inputs[$key];
            if ($type === 'number') {
                if (!\is_int($rawValue) && !\is_float($rawValue) && !(\is_string($rawValue) && \is_numeric($rawValue))) {
                    throw new ApiException(
                        ApiError::CalculatorInvalidType,
                        null,
                        ['messages' => ["Поле {$key} должно быть числом."]],
                    );
                }
                $v = (float) $rawValue;

                $min = $inputDef['min'] ?? null;
                $max = $inputDef['max'] ?? null;
                if ($min !== null && \is_numeric($min) && $v < (float) $min) {
                    throw new ApiException(
                        ApiError::CalculatorInvalidRange,
                        null,
                        ['messages' => ["Поле {$key} должно быть в диапазоне."]],
                    );
                }
                if ($max !== null && \is_numeric($max) && $v > (float) $max) {
                    throw new ApiException(
                        ApiError::CalculatorInvalidRange,
                        null,
                        ['messages' => ["Поле {$key} должно быть в диапазоне."]],
                    );
                }
            } elseif ($type === 'select') {
                if (!\is_string($rawValue)) {
                    throw new ApiException(
                        ApiError::CalculatorInvalidType,
                        null,
                        ['messages' => ["Поле {$key} должно быть строкой."]],
                    );
                }
                $options = $inputDef['options'] ?? [];
                $allowed = [];
                if (\is_array($options)) {
                    foreach ($options as $opt) {
                        if (\is_array($opt) && isset($opt['value'])) {
                            $allowed[] = (string) $opt['value'];
                        }
                    }
                }
                if ($allowed !== [] && !\in_array($rawValue, $allowed, true)) {
                    throw new ApiException(
                        ApiError::CalculatorInvalidRange,
                        null,
                        ['messages' => ["Поле {$key} имеет недопустимое значение."]],
                    );
                }
            }
        }

        // Calculator-specific invariants (align with iOS mock behaviour).
        if ($calculatorId === 'body_fat') {
            $sex = isset($inputs['sex']) ? (string) $inputs['sex'] : null;
            $heightCm = isset($inputs['heightCm']) ? (float) $inputs['heightCm'] : 0.0;
            $neckCm = isset($inputs['neckCm']) ? (float) $inputs['neckCm'] : 0.0;
            $waistCm = isset($inputs['waistCm']) ? (float) $inputs['waistCm'] : 0.0;
            $hipsCm = isset($inputs['hipsCm']) ? (float) $inputs['hipsCm'] : 0.0;

            if ($heightCm <= 0 || $neckCm <= 0 || $waistCm <= 0) {
                throw new ApiException(
                    ApiError::CalculatorInvalidRange,
                    null,
                    ['messages' => ['Некорректные измерения']],
                );
            }
            if ($sex === 'male') {
                $a = $waistCm - $neckCm;
                if ($a <= 0) {
                    throw new ApiException(
                        ApiError::CalculatorInvalidRange,
                        null,
                        ['messages' => ['Талия должна быть больше шеи']],
                    );
                }
            } elseif ($sex === 'female') {
                $b = $waistCm + $hipsCm - $neckCm;
                if ($b <= 0) {
                    throw new ApiException(
                        ApiError::CalculatorInvalidRange,
                        null,
                        ['messages' => ['Некорректные значения бёдер/талии/шеи']],
                    );
                }
            }
        }

        if ($calculatorId === 'bju') {
            $age = isset($inputs['age']) ? (float) $inputs['age'] : 0.0;
            $heightCm = isset($inputs['heightCm']) ? (float) $inputs['heightCm'] : 0.0;
            $weightKg = isset($inputs['weightKg']) ? (float) $inputs['weightKg'] : 0.0;
            if ($age <= 0 || $heightCm <= 0 || $weightKg <= 0) {
                throw new ApiException(
                    ApiError::CalculatorInvalidRange,
                    null,
                    ['messages' => ['Некорректные входные данные']],
                );
            }
        }

        if ($calculatorId === 'bench_1rm') {
            $reps = isset($inputs['reps']) ? (float) $inputs['reps'] : 0.0;
            $weightKg = isset($inputs['weightKg']) ? (float) $inputs['weightKg'] : 0.0;
            if ($reps < 1 || $reps > 30 || $weightKg <= 0) {
                throw new ApiException(
                    ApiError::CalculatorInvalidRange,
                    null,
                    ['messages' => ['Введите корректные данные для теста']],
                );
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $definitionInputs
     * @param array<string, mixed> $inputs
     * @param array<string, bool> $visibleMap
     * @return array<string, float>
     */
    private function buildNumericEnv(string $calculatorId, array $definitionInputs, array $inputs, array $visibleMap): array
    {
        $env = [];

        foreach ($definitionInputs as $inputDef) {
            if (!\is_array($inputDef) || !isset($inputDef['key'], $inputDef['type'])) {
                continue;
            }

            $key = (string) $inputDef['key'];
            $type = (string) $inputDef['type'];

            if ($type === 'number') {
                $raw = $inputs[$key] ?? 0.0;
                $env[$key] = (float) $raw;
                continue;
            }

            // select -> numeric mapping
            if (!\array_key_exists($key, $inputs) && ($visibleMap[$key] ?? true) === false) {
                $env[$key] = 0.0;
                continue;
            }

            $rawSel = $inputs[$key] ?? '';
            $rawSel = \is_string($rawSel) ? $rawSel : (string) $rawSel;
            $env[$key] = $this->mapSelectToNumeric($calculatorId, $key, $rawSel);
        }

        return $env;
    }

    private function mapSelectToNumeric(string $calculatorId, string $inputKey, string $rawValue): float
    {
        return match ($calculatorId) {
            'body_fat' => match ($inputKey) {
                'sex' => match ($rawValue) {
                    'male' => 1.0,
                    'female' => 0.0,
                    default => throw new ApiException(ApiError::CalculatorInvalidType, null, ['messages' => ['Некорректное значение пола']]),
                },
                default => 0.0,
            },
            'protein_norm' => match ($inputKey) {
                'goal' => match ($rawValue) {
                    'deficit' => 2.0,
                    'maintain' => 1.6,
                    'gain' => 1.8,
                    default => throw new ApiException(ApiError::CalculatorInvalidType, null, ['messages' => ['Некорректная цель']]),
                },
                default => 0.0,
            },
            'water_balance' => match ($inputKey) {
                'activity' => match ($rawValue) {
                    'low' => 0.0,
                    'moderate' => 400.0,
                    'high' => 800.0,
                    default => throw new ApiException(ApiError::CalculatorInvalidType, null, ['messages' => ['Некорректная активность']]),
                },
                // Extended multi-step water balance model
                'sex' => match ($rawValue) {
                    'male' => 150.0,
                    'female' => 0.0,
                    default => throw new ApiException(ApiError::CalculatorInvalidType, null, ['messages' => ['Некорректное значение пола']]),
                },
                'state' => match ($rawValue) {
                    'normal' => 0.0,
                    'pregnancy' => 300.0,
                    'lactation' => 700.0,
                    'illness' => 500.0,
                    default => throw new ApiException(ApiError::CalculatorInvalidType, null, ['messages' => ['Некорректное состояние']]),
                },
                'activityLevel' => match ($rawValue) {
                    'low' => 0.0,
                    'moderate' => 300.0,
                    'high' => 600.0,
                    default => throw new ApiException(ApiError::CalculatorInvalidType, null, ['messages' => ['Некорректный уровень активности']]),
                },
                'workType' => match ($rawValue) {
                    'office' => 0.0,
                    'mixed' => 250.0,
                    'physical' => 450.0,
                    default => throw new ApiException(ApiError::CalculatorInvalidType, null, ['messages' => ['Некорректный тип работы']]),
                },
                'temperatureBand' => match ($rawValue) {
                    'cool' => 0.0,
                    'warm' => 300.0,
                    'hot' => 600.0,
                    default => throw new ApiException(ApiError::CalculatorInvalidType, null, ['messages' => ['Некорректная температура']]),
                },
                'humidityBand' => match ($rawValue) {
                    'low' => 0.0,
                    'medium' => 150.0,
                    'high' => 300.0,
                    default => throw new ApiException(ApiError::CalculatorInvalidType, null, ['messages' => ['Некорректная влажность']]),
                },
                'extraConditions' => match ($rawValue) {
                    'none' => 0.0,
                    'air_travel' => 250.0,
                    'sauna' => 500.0,
                    default => throw new ApiException(ApiError::CalculatorInvalidType, null, ['messages' => ['Некорректные доп. условия']]),
                },
                default => 0.0,
            },
            'bju' => match ($inputKey) {
                'sex' => match ($rawValue) {
                    'male' => 5.0,
                    'female' => -161.0,
                    default => throw new ApiException(ApiError::CalculatorInvalidType, null, ['messages' => ['Некорректное значение пола']]),
                },
                'activity' => match ($rawValue) {
                    'low' => 1.2,
                    'light' => 1.375,
                    'moderate' => 1.55,
                    'high' => 1.725,
                    'extreme' => 1.9,
                    default => throw new ApiException(ApiError::CalculatorInvalidType, null, ['messages' => ['Некорректная активность']]),
                },
                'goal' => match ($rawValue) {
                    'deficit' => 0.85,
                    'maintain' => 1.0,
                    'surplus' => 1.1,
                    default => throw new ApiException(ApiError::CalculatorInvalidType, null, ['messages' => ['Некорректная цель']]),
                },
                default => 0.0,
            },
            default => throw new ApiException(ApiError::CalculatorInvalidDefinition),
        };
    }

    /**
     * @param array<string, float> $variables
     * @param array<int, string> $allowedVariables
     */
    private function evaluateExpressionSafely(string $expression, array $variables, array $allowedVariables): float
    {
        try {
            return $this->expressionEvaluator->evaluate($expression, $variables, $allowedVariables);
        } catch (ApiException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ApiException(ApiError::CalculatorInvalidDefinition, null, ['messages' => ['Некорректное выражение']]);
        }
    }

    private function roundOutputValue(string $calculatorId, string $outputKey, float $value): float
    {
        return match (true) {
            $outputKey === 'waterLiters' => $this->roundToDecimals($value, 2),
            $outputKey === 'bmi' => $this->roundToDecimals($value, 1),
            $outputKey === 'bodyFatPercent' => $this->roundToDecimals($value, 1),
            $outputKey === 'proteinGrams' => $this->roundToDecimals($value, 1),
            $calculatorId === 'bench_1rm' => $this->roundToDecimals($value, 1),
            $calculatorId === 'bju' => $this->roundToDecimals($value, 0),
            default => $this->roundToDecimals($value, 0),
        };
    }

    private function roundToDecimals(float $v, int $decimals): float
    {
        $factor = 10 ** $decimals;
        $scaled = $v * $factor;
        $sign = $scaled < 0 ? -1.0 : 1.0;
        $abs = \abs($scaled);
        $roundedAbs = \floor($abs + 0.5);
        $rounded = $sign * $roundedAbs / $factor;
        return $rounded;
    }

    /**
     * @param array<string, mixed> $outputs
     * @return array{label: string, subtitle: string}|null
     */
    private function computeInterpretation(string $calculatorId, array $inputs, array $outputs, array $visibleMap): ?array
    {
        if ($calculatorId === 'bmi') {
            $bmi = (float) ($outputs['bmi'] ?? 0.0);
            if ($bmi < 18.5) {
                return ['label' => 'Дефицит', 'subtitle' => 'Проверьте питание и восстановление.'];
            }
            if ($bmi <= 24.9) {
                return ['label' => 'Норма', 'subtitle' => 'В пределах нормы.'];
            }
            if ($bmi <= 29.9) {
                return ['label' => 'Избыток', 'subtitle' => 'Может быть полезна корректировка питания и активности.'];
            }
            return ['label' => 'Ожирение', 'subtitle' => 'Рекомендуется консультация специалиста.'];
        }

        if ($calculatorId === 'body_fat') {
            $sex = isset($inputs['sex']) ? (string) $inputs['sex'] : 'male';
            $percent = (float) ($outputs['bodyFatPercent'] ?? 0.0);

            if ($sex === 'male') {
                if ($percent < 10) {
                    return ['label' => 'Отлично', 'subtitle' => 'Уровень жировой ткани в хороших пределах.'];
                }
                if ($percent < 18) {
                    return ['label' => 'Хорошо', 'subtitle' => 'В целом в норме, можно улучшать прогресс.'];
                }
                if ($percent < 25) {
                    return ['label' => 'Средне', 'subtitle' => 'Требуется работа с дефицитом/активностью.'];
                }
                return ['label' => 'Высокий', 'subtitle' => 'Повысьте активность и отрегулируйте калории.'];
            }

            if ($percent < 16) {
                return ['label' => 'Отлично', 'subtitle' => 'Уровень жировой ткани в хорошем диапазоне.'];
            }
            if ($percent < 25) {
                return ['label' => 'Хорошо', 'subtitle' => 'Можно поддерживать режим и прогресс.'];
            }
            if ($percent < 32) {
                return ['label' => 'Средне', 'subtitle' => 'Полезна аккуратная корректировка питания.'];
            }
            return ['label' => 'Высокий', 'subtitle' => 'Нужна системная работа с калориями и активностью.'];
        }

        return null;
    }

    private function computeSummary(string $calculatorId, array $outputs, ?array $interpretation): ?string
    {
        return match ($calculatorId) {
            'bmi' => $this->summaryBmi($outputs['bmi'] ?? 0.0, $interpretation),
            'protein_norm' => $this->summaryProteinNorm((float) ($outputs['proteinGrams'] ?? 0.0)),
            'water_balance' => $this->summaryWaterBalance((float) ($outputs['waterLiters'] ?? 0.0)),
            'bju' => $this->summaryBju((float) ($outputs['caloriesKcal'] ?? 0.0)),
            'bench_1rm' => $this->summaryBench1Rm((float) ($outputs['oneRmKg'] ?? 0.0)),
            'body_fat' => $this->summaryBodyFat((float) ($outputs['bodyFatPercent'] ?? 0.0)),
            default => null,
        };
    }

    private function summaryBmi(float $bmi, ?array $interpretation): ?string
    {
        if ($interpretation === null) {
            return null;
        }
        $label = $interpretation['label'] ?? '';
        $rounded = $this->roundToDecimals($bmi, 1);
        $fixed = \number_format($rounded, 1, '.', '');
        return "ИМТ: {$fixed}. {$label}";
    }

    private function formattedTo0_1(float $v): string
    {
        $rounded = $this->roundToDecimals($v, 1);
        if ($rounded === \floor($rounded)) {
            return (string) (int) $rounded;
        }
        $s = \number_format($rounded, 1, '.', '');
        // Trim trailing .0 (just in case of floating noise).
        return \str_ends_with($s, '.0') ? (string) (int) (float) $s : $s;
    }

    private function formattedTo0_2(float $v): string
    {
        $rounded = $this->roundToDecimals($v, 2);
        $s = \number_format($rounded, 2, '.', '');
        $s = \rtrim($s, '0');
        $s = \rtrim($s, '.');
        return $s === '' ? '0' : $s;
    }

    private function summaryProteinNorm(float $proteinGrams): string
    {
        $rounded = $this->roundToDecimals($proteinGrams, 1);
        return "Белок: {$this->formattedTo0_1($rounded)} г/день";
    }

    private function summaryWaterBalance(float $waterLiters): string
    {
        $rounded = $this->roundToDecimals($waterLiters, 2);
        return "Норма воды: {$this->formattedTo0_2($rounded)} л/день";
    }

    private function summaryBju(float $caloriesKcal): string
    {
        $rounded = $this->roundToDecimals($caloriesKcal, 0);
        return "Калории: " . (int) $rounded . " ккал/день";
    }

    private function summaryBench1Rm(float $oneRmKg): string
    {
        $rounded = $this->roundToDecimals($oneRmKg, 1);
        $fixed = \number_format($rounded, 1, '.', '');
        return "1RM: {$fixed} кг";
    }

    private function summaryBodyFat(float $percent): string
    {
        $rounded = $this->roundToDecimals($percent, 1);
        $fixed = \number_format($rounded, 1, '.', '');
        return "Процент жира: {$fixed}%";
    }

    /**
     * @param array<string, float> $outputs
     * @param array{label:string,subtitle:string}|null $interpretation
     * @return list<array{title:string,description:string}>
     */
    private function computeResultDescriptions(string $calculatorId, array $outputs, ?array $interpretation): array
    {
        return match ($calculatorId) {
            'water_balance' => [[
                'title' => 'Ваш результат',
                'description' => 'Рекомендуемая суточная норма воды: ' . $this->formattedTo0_2((float) ($outputs['waterLiters'] ?? 0.0)) . ' л/день.',
            ]],
            'bmi' => [[
                'title' => 'Ваш результат',
                'description' => $this->summaryBmi((float) ($outputs['bmi'] ?? 0.0), $interpretation) ?? 'Результат рассчитан.',
            ]],
            default => [],
        };
    }

    /**
     * @param array<string, float> $outputs
     * @param array{label:string,subtitle:string}|null $interpretation
     * @return list<array{title:string,description:string}>
     */
    private function computeRecommendations(string $calculatorId, array $outputs, ?array $interpretation): array
    {
        if ($calculatorId === 'water_balance') {
            $liters = (float) ($outputs['waterLiters'] ?? 0.0);
            $perPortion = $liters > 0 ? $liters / 8.0 : 0.0;
            return [[
                'title' => 'Что делать дальше',
                'description' => 'Распределяйте воду равномерно в течение дня: 6-8 приемов, ориентир ~' . $this->formattedTo0_2($perPortion) . ' л за раз.',
            ]];
        }

        if ($calculatorId === 'bmi' && $interpretation !== null) {
            return [[
                'title' => 'Что делать дальше',
                'description' => $interpretation['subtitle'] ?: 'Поддерживайте регулярный режим питания и активности.',
            ]];
        }

        return [];
    }

    /**
     * Ensure optional definition.flow is consistent with inputs[].key contract.
     *
     * @param array<string, mixed> $definition
     */
    private function validateFlowContract(array $definition): void
    {
        $flow = $definition['flow'] ?? null;
        if ($flow === null) {
            return;
        }
        if (!\is_array($flow)) {
            throw new ApiException(ApiError::CalculatorInvalidDefinition);
        }

        $mode = $flow['mode'] ?? 'single';
        if (!\is_string($mode) || !\in_array($mode, ['single', 'multistep'], true)) {
            throw new ApiException(ApiError::CalculatorInvalidDefinition);
        }

        if ($mode !== 'multistep') {
            return;
        }

        $steps = $flow['steps'] ?? null;
        if (!\is_array($steps) || $steps === []) {
            throw new ApiException(ApiError::CalculatorInvalidDefinition);
        }

        $inputs = $definition['inputs'] ?? null;
        if (!\is_array($inputs)) {
            throw new ApiException(ApiError::CalculatorInvalidDefinition);
        }

        $inputKeys = [];
        foreach ($inputs as $input) {
            if (!\is_array($input) || !isset($input['key']) || !\is_string($input['key'])) {
                throw new ApiException(ApiError::CalculatorInvalidDefinition);
            }
            $inputKeys[] = $input['key'];
        }

        foreach ($steps as $step) {
            if (!\is_array($step)) {
                throw new ApiException(ApiError::CalculatorInvalidDefinition);
            }
            $stepInputKeys = $step['inputKeys'] ?? null;
            if (!\is_array($stepInputKeys)) {
                throw new ApiException(ApiError::CalculatorInvalidDefinition);
            }
            foreach ($stepInputKeys as $key) {
                if (!\is_string($key) || !\in_array($key, $inputKeys, true)) {
                    throw new ApiException(ApiError::CalculatorInvalidDefinition);
                }
            }
        }
    }
}

