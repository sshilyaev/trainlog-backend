<?php

declare(strict_types=1);

namespace App\Http\Request\Supplement;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateSupplementAssignmentRequest
{
    #[Assert\NotBlank(message: ValidationMessage::CoachProfileIdRequired->value)]
    #[Assert\Uuid(message: ValidationMessage::CoachProfileIdUuid->value)]
    public string $coachProfileId = '';

    #[Assert\NotBlank(message: ValidationMessage::TraineeProfileIdRequired->value)]
    #[Assert\Uuid(message: ValidationMessage::TraineeProfileIdUuid->value)]
    public string $traineeProfileId = '';

    #[Assert\NotBlank(message: 'Укажите supplementId')]
    #[Assert\Uuid(message: 'supplementId должен быть валидным UUID')]
    public string $supplementId = '';

    #[Assert\Length(max: 255)]
    public ?string $dosage = null;

    #[Assert\Length(max: 64)]
    public ?string $dosageValue = null;

    #[Assert\Choice(
        choices: ['capsule', 'tablet', 'gram', 'milligram', 'milliliter', 'iu', 'scoop', 'drop', 'serving'],
        message: 'dosageUnit должен быть одним из: capsule, tablet, gram, milligram, milliliter, iu, scoop, drop, serving'
    )]
    public ?string $dosageUnit = null;

    #[Assert\Length(max: 255)]
    public ?string $timing = null;

    #[Assert\Length(max: 255)]
    public ?string $frequency = null;

    #[Assert\Length(max: 1000)]
    public ?string $note = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'coachProfileId' => $this->coachProfileId,
            'traineeProfileId' => $this->traineeProfileId,
            'supplementId' => $this->supplementId,
            'dosage' => $this->dosage,
            'dosageValue' => $this->dosageValue,
            'dosageUnit' => $this->dosageUnit,
            'timing' => $this->timing,
            'frequency' => $this->frequency,
            'note' => $this->note,
        ];
    }
}

