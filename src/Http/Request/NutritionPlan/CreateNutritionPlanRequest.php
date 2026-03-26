<?php

declare(strict_types=1);

namespace App\Http\Request\NutritionPlan;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateNutritionPlanRequest
{
    #[Assert\NotBlank(message: ValidationMessage::CoachProfileIdRequired->value)]
    #[Assert\Uuid(message: ValidationMessage::CoachProfileIdUuid->value)]
    public string $coachProfileId = '';

    #[Assert\NotBlank(message: ValidationMessage::TraineeProfileIdRequired->value)]
    #[Assert\Uuid(message: ValidationMessage::TraineeProfileIdUuid->value)]
    public string $traineeProfileId = '';

    #[Assert\NotBlank(message: 'Укажите proteinPerKg')]
    #[Assert\Positive(message: 'proteinPerKg должен быть > 0')]
    public float $proteinPerKg = 0.0;

    #[Assert\NotBlank(message: 'Укажите fatPerKg')]
    #[Assert\Positive(message: 'fatPerKg должен быть > 0')]
    public float $fatPerKg = 0.0;

    #[Assert\NotBlank(message: 'Укажите carbsPerKg')]
    #[Assert\Positive(message: 'carbsPerKg должен быть > 0')]
    public float $carbsPerKg = 0.0;

    #[Assert\Positive(message: 'weightKg должен быть > 0')]
    public ?float $weightKg = null;

    #[Assert\Length(max: 4000)]
    public ?string $comment = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'coachProfileId' => $this->coachProfileId,
            'traineeProfileId' => $this->traineeProfileId,
            'proteinPerKg' => $this->proteinPerKg,
            'fatPerKg' => $this->fatPerKg,
            'carbsPerKg' => $this->carbsPerKg,
            'weightKg' => $this->weightKg,
            'comment' => $this->comment,
        ];
    }
}

