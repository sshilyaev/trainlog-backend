<?php

declare(strict_types=1);

namespace App\Http\Request\NutritionPlan;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class ListNutritionPlansRequest
{
    public function __construct(
        #[Assert\Uuid(message: ValidationMessage::CoachProfileIdUuid->value)]
        public ?string $coachProfileId = null,
        #[Assert\Uuid(message: ValidationMessage::TraineeProfileIdUuid->value)]
        public ?string $traineeProfileId = null,
        #[Assert\Choice(choices: ['trainee'], message: 'as должен быть trainee')]
        public ?string $as = null,
        #[Assert\Choice(choices: ['coachProfiles'], message: 'embed должен быть coachProfiles')]
        public ?string $embed = null,
    ) {
    }
}

