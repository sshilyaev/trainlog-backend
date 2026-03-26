<?php

declare(strict_types=1);

namespace App\Http\Request\CoachTraineeLink;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class ListCoachTraineeLinksRequest
{
    public function __construct(
        #[Assert\NotBlank(message: ValidationMessage::ProfileIdRequired->value)]
        #[Assert\Uuid(message: ValidationMessage::ProfileIdUuid->value)]
        public string $profileId = '',

        #[Assert\NotBlank(message: ValidationMessage::AsRequired->value)]
        #[Assert\Choice(choices: ['coach', 'trainee'], message: ValidationMessage::AsCoachOrTrainee->value)]
        public string $as = '',

        /** Optional: embed=profiles to include profile objects (trainee or coach) in response */
        public ?string $embed = null,
    ) {
    }
}
