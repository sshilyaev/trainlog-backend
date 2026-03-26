<?php

declare(strict_types=1);

namespace App\Http\Request\Measurement;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateMeasurementRequest
{
    #[Assert\Date(message: ValidationMessage::DateFormat->value)]
    public ?string $date = null;

    public ?float $weight = null;
    public ?float $height = null;
    public ?float $neck = null;
    public ?float $shoulders = null;
    public ?float $leftBiceps = null;
    public ?float $rightBiceps = null;
    public ?float $waist = null;
    public ?float $belly = null;
    public ?float $chest = null;
    public ?float $leftThigh = null;
    public ?float $rightThigh = null;
    public ?float $hips = null;
    public ?float $buttocks = null;
    public ?float $leftCalf = null;
    public ?float $rightCalf = null;
    public ?string $note = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'date' => $this->date,
            'weight' => $this->weight,
            'height' => $this->height,
            'neck' => $this->neck,
            'shoulders' => $this->shoulders,
            'leftBiceps' => $this->leftBiceps,
            'rightBiceps' => $this->rightBiceps,
            'waist' => $this->waist,
            'belly' => $this->belly,
            'chest' => $this->chest,
            'leftThigh' => $this->leftThigh,
            'rightThigh' => $this->rightThigh,
            'hips' => $this->hips,
            'buttocks' => $this->buttocks,
            'leftCalf' => $this->leftCalf,
            'rightCalf' => $this->rightCalf,
            'note' => $this->note,
        ], static fn ($v) => $v !== null);
    }
}
