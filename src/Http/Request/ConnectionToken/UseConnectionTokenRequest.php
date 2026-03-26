<?php

declare(strict_types=1);

namespace App\Http\Request\ConnectionToken;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class UseConnectionTokenRequest
{
    #[Assert\NotBlank(message: ValidationMessage::TokenRequired->value)]
    public string $token = '';

    #[Assert\NotBlank(message: ValidationMessage::CoachProfileIdRequired->value)]
    #[Assert\Uuid(message: ValidationMessage::CoachProfileIdUuid->value)]
    public string $coachProfileId = '';

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'coachProfileId' => $this->coachProfileId,
        ];
    }
}
