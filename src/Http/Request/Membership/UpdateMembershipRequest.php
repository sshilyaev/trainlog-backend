<?php

declare(strict_types=1);

namespace App\Http\Request\Membership;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateMembershipRequest
{
    #[Assert\Choice(choices: ['active', 'finished', 'cancelled'], message: ValidationMessage::StatusActiveFinishedCancelled->value)]
    public ?string $status = null;

    #[Assert\GreaterThanOrEqual(value: 0, message: 'freezeDays не может быть отрицательным')]
    public ?int $freezeDays = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [];
        if ($this->status !== null) {
            $out['status'] = $this->status;
        }
        if ($this->freezeDays !== null) {
            $out['freezeDays'] = $this->freezeDays;
        }
        return $out;
    }
}
