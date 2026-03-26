<?php

declare(strict_types=1);

namespace App\Http\Request\Goal;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateGoalRequest
{
    #[Assert\NotBlank(message: ValidationMessage::ProfileIdRequired->value)]
    #[Assert\Uuid(message: ValidationMessage::ProfileIdUuid->value)]
    public string $profileId = '';

    #[Assert\NotBlank(message: ValidationMessage::MeasurementTypeRequired->value)]
    public string $measurementType = '';

    #[Assert\Type(type: 'numeric', message: ValidationMessage::TargetValueNumeric->value)]
    public float $targetValue = 0.0;

    #[Assert\NotBlank(message: ValidationMessage::TargetDateRequired->value)]
    #[Assert\Date(message: ValidationMessage::TargetDateFormat->value)]
    public string $targetDate = '';

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'profileId' => $this->profileId,
            'measurementType' => $this->measurementType,
            'targetValue' => $this->targetValue,
            'targetDate' => $this->targetDate,
        ];
    }
}
