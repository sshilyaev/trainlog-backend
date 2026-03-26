<?php

declare(strict_types=1);

namespace App\Http\Request\Goal;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateGoalRequest
{
    public ?string $measurementType = null;
    public ?float $targetValue = null;

    #[Assert\Date(message: ValidationMessage::TargetDateFormat->value)]
    public ?string $targetDate = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'measurementType' => $this->measurementType,
            'targetValue' => $this->targetValue,
            'targetDate' => $this->targetDate,
        ], static fn ($v) => $v !== null);
    }
}
