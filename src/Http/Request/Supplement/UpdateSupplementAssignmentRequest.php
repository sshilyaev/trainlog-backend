<?php

declare(strict_types=1);

namespace App\Http\Request\Supplement;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateSupplementAssignmentRequest
{
    #[Assert\Uuid(message: 'supplementId должен быть валидным UUID')]
    public ?string $supplementId = null;

    #[Assert\Length(max: 255)]
    public ?string $dosage = null;

    #[Assert\Length(max: 255)]
    public ?string $timing = null;

    #[Assert\Length(max: 255)]
    public ?string $frequency = null;

    #[Assert\Length(max: 1000)]
    public ?string $note = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'supplementId' => $this->supplementId,
            'dosage' => $this->dosage,
            'timing' => $this->timing,
            'frequency' => $this->frequency,
            'note' => $this->note,
        ], static fn ($v): bool => $v !== null);
    }
}

