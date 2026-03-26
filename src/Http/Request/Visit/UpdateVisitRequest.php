<?php

declare(strict_types=1);

namespace App\Http\Request\Visit;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateVisitRequest
{
    #[Assert\Choice(choices: ['cancelled'], message: ValidationMessage::StatusCancelledForVisit->value)]
    public ?string $status = null;

    #[Assert\Choice(choices: ['paid', 'debt'], message: 'paymentStatus must be paid or debt')]
    public ?string $paymentStatus = null;

    #[Assert\Uuid(message: ValidationMessage::MembershipIdUuid->value)]
    public ?string $membershipId = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'status' => $this->status,
            'paymentStatus' => $this->paymentStatus,
            'membershipId' => $this->membershipId,
        ], static fn ($v) => $v !== null);
    }
}
