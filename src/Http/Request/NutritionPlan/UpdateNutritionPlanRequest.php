<?php

declare(strict_types=1);

namespace App\Http\Request\NutritionPlan;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateNutritionPlanRequest
{
    #[Assert\Positive(message: 'proteinPerKg должен быть > 0')]
    public ?float $proteinPerKg = null;

    #[Assert\Positive(message: 'fatPerKg должен быть > 0')]
    public ?float $fatPerKg = null;

    #[Assert\Positive(message: 'carbsPerKg должен быть > 0')]
    public ?float $carbsPerKg = null;

    #[Assert\Positive(message: 'weightKg должен быть > 0')]
    public ?float $weightKg = null;

    #[Assert\Length(max: 4000)]
    public ?string $comment = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'proteinPerKg' => $this->proteinPerKg,
            'fatPerKg' => $this->fatPerKg,
            'carbsPerKg' => $this->carbsPerKg,
            'weightKg' => $this->weightKg,
            'comment' => $this->comment,
        ], static fn ($v): bool => $v !== null);
    }
}

