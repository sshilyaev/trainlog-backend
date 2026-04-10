<?php

declare(strict_types=1);

namespace App\Http\Request\Supplement;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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

    public ?string $dosageUnit = null;

    #[Assert\Length(max: 255)]
    public ?string $timing = null;

    #[Assert\Length(max: 255)]
    public ?string $frequency = null;

    #[Assert\Length(max: 1000)]
    public ?string $note = null;

    #[Assert\Callback]
    public function validateDosageUnitField(ExecutionContextInterface $context): void
    {
        $raw = $this->dosageUnit;
        if ($raw === null) {
            return;
        }
        $t = trim($raw);
        if ($t === '') {
            $this->dosageUnit = null;

            return;
        }
        $canonical = SupplementDosageUnitNormalizer::normalizeOptional($raw);
        if ($canonical === null) {
            $context->buildViolation('dosageUnit должен быть одним из: capsule, tablet, gram, milligram, milliliter, iu, scoop, drop, serving')
                ->atPath('dosageUnit')
                ->addViolation();

            return;
        }
        $this->dosageUnit = $canonical;
    }

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

