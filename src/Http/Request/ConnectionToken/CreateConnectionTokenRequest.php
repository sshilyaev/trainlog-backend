<?php

declare(strict_types=1);

namespace App\Http\Request\ConnectionToken;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateConnectionTokenRequest
{
    #[Assert\NotBlank(message: ValidationMessage::TraineeProfileIdRequired->value)]
    #[Assert\Uuid(message: ValidationMessage::TraineeProfileIdUuid->value)]
    public string $traineeProfileId = '';

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['traineeProfileId' => $this->traineeProfileId];
    }
}
