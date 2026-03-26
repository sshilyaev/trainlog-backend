<?php

declare(strict_types=1);

namespace App\Http\Request\Membership;

use App\Entity\Membership;
use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class CreateMembershipRequest
{
    #[Assert\NotBlank(message: ValidationMessage::CoachProfileIdRequired->value)]
    #[Assert\Uuid(message: ValidationMessage::CoachProfileIdUuid->value)]
    public string $coachProfileId = '';

    #[Assert\NotBlank(message: ValidationMessage::TraineeProfileIdRequired->value)]
    #[Assert\Uuid(message: ValidationMessage::TraineeProfileIdUuid->value)]
    public string $traineeProfileId = '';

    #[Assert\Choice(choices: [Membership::KIND_BY_VISITS, Membership::KIND_UNLIMITED], message: 'kind должен быть by_visits или unlimited')]
    public string $kind = Membership::KIND_BY_VISITS;

    /** For by_visits: required and >= 1. For unlimited: ignored (can be 0). */
    public int $totalSessions = 0;

    /** Required for unlimited. Format Y-m-d. */
    public ?string $startDate = null;

    /** Required for unlimited. Format Y-m-d. */
    public ?string $endDate = null;

    /** Optional for unlimited. Default 0. */
    public int $freezeDays = 0;

    public ?int $priceRub = null;

    #[Assert\Callback]
    public function validateKindDependent(ExecutionContextInterface $context): void
    {
        if ($this->kind === Membership::KIND_UNLIMITED) {
            if ($this->startDate === null || $this->startDate === '') {
                $context->buildViolation('Для безлимитного абонемента укажите startDate')->atPath('startDate')->addViolation();
            }
            if ($this->endDate === null || $this->endDate === '') {
                $context->buildViolation('Для безлимитного абонемента укажите endDate')->atPath('endDate')->addViolation();
            }
            if ($this->freezeDays < 0) {
                $context->buildViolation('freezeDays не может быть отрицательным')->atPath('freezeDays')->addViolation();
            }
        } else {
            if ($this->totalSessions < 1) {
                $context->buildViolation(ValidationMessage::TotalSessionsAtLeast1->value)->atPath('totalSessions')->addViolation();
            }
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [
            'coachProfileId' => $this->coachProfileId,
            'traineeProfileId' => $this->traineeProfileId,
            'kind' => $this->kind,
            'totalSessions' => $this->kind === Membership::KIND_UNLIMITED ? 0 : $this->totalSessions,
            'priceRub' => $this->priceRub,
        ];
        if ($this->kind === Membership::KIND_UNLIMITED) {
            $out['startDate'] = $this->startDate;
            $out['endDate'] = $this->endDate;
            $out['freezeDays'] = $this->freezeDays;
        }
        return $out;
    }
}
