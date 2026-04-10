<?php

declare(strict_types=1);

namespace App\Http\Request\Supplement;

/**
 * Канонические значения dosageUnit в API (совпадают с iOS SupplementDosageUnit.rawValue).
 */
final class SupplementDosageUnitNormalizer
{
    /** @var list<string> */
    public const CANONICAL_UNITS = ['capsule', 'tablet', 'gram', 'milligram', 'milliliter', 'iu', 'scoop', 'drop', 'serving'];

    /**
     * null, пустая строка и пробелы → null (сброс).
     * Узнаваемые синонимы и регистр → каноническое значение.
     * Непустая нераспознанная строка → null (вызывающий код должен добавить нарушение).
     */
    public static function normalizeOptional(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $t = trim($value);
        if ($t === '') {
            return null;
        }

        foreach (self::CANONICAL_UNITS as $u) {
            if (strcasecmp($t, $u) === 0) {
                return $u;
            }
        }

        $lower = strtolower($t);
        /** @var array<string, string> */
        $aliases = [
            'me' => 'iu',
            'mg' => 'milligram',
            'g' => 'gram',
            'ml' => 'milliliter',
            'millilitre' => 'milliliter',
        ];

        if (isset($aliases[$lower])) {
            return $aliases[$lower];
        }

        // Кириллица «МЕ» (международные единицы) и варианты, если клиент когда‑либо пришлёт не ASCII.
        $lowerUtf = mb_strtolower($t, 'UTF-8');
        /** @var array<string, string> */
        $unicodeAliases = [
            'ме' => 'iu',
            'мэ' => 'iu',
        ];
        if (isset($unicodeAliases[$lowerUtf])) {
            return $unicodeAliases[$lowerUtf];
        }

        return null;
    }
}
