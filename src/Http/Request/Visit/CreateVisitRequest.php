<?php

declare(strict_types=1);

namespace App\Http\Request\Visit;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateVisitRequest
{
    #[Assert\NotBlank(message: ValidationMessage::CoachProfileIdRequired->value)]
    #[Assert\Uuid(message: ValidationMessage::CoachProfileIdUuid->value)]
    public string $coachProfileId = '';

    #[Assert\NotBlank(message: ValidationMessage::TraineeProfileIdRequired->value)]
    #[Assert\Uuid(message: ValidationMessage::TraineeProfileIdUuid->value)]
    public string $traineeProfileId = '';

    #[Assert\NotBlank(message: ValidationMessage::DateRequired->value)]
    #[Assert\Date(message: ValidationMessage::DateFormat->value)]
    public string $date = '';

    /** paid = оплачено (разово или с абонемента), debt = долг. Необязательно. */
    public ?string $paymentStatus = null;

    #[Assert\Uuid(message: ValidationMessage::MembershipIdUuid->value)]
    public ?string $membershipId = null;

    /**
     * Идемпотентный ключ для безопасного повторного создания визита (офлайн-режим).
     * Необязательно, но если задан — сервер попытается вернуть уже созданный визит.
     */
    public ?string $idempotencyKey = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'coachProfileId' => $this->coachProfileId,
            'traineeProfileId' => $this->traineeProfileId,
            'date' => $this->date,
        ];
        if ($this->paymentStatus !== null) {
            $data['paymentStatus'] = $this->paymentStatus;
        }
        if ($this->membershipId !== null && $this->membershipId !== '') {
            $data['membershipId'] = $this->membershipId;
        }
        if ($this->idempotencyKey !== null && $this->idempotencyKey !== '') {
            $data['idempotencyKey'] = $this->idempotencyKey;
        }
        return $data;
    }
}
