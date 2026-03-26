<?php

declare(strict_types=1);

namespace App\Http\Request\Profile;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class MergeProfilesRequest
{
    #[Assert\NotBlank(message: ValidationMessage::CoachProfileIdRequired->value)]
    #[Assert\Uuid(message: ValidationMessage::CoachProfileIdUuid->value)]
    public string $coachProfileId = '';

    #[Assert\NotBlank(message: 'Укажите managedTraineeProfileId')]
    #[Assert\Uuid(message: ValidationMessage::TraineeProfileIdUuid->value)]
    public string $managedTraineeProfileId = '';

    #[Assert\NotBlank(message: 'Укажите realTraineeProfileId')]
    #[Assert\Uuid(message: ValidationMessage::TraineeProfileIdUuid->value)]
    public string $realTraineeProfileId = '';

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'coachProfileId' => $this->coachProfileId,
            'managedTraineeProfileId' => $this->managedTraineeProfileId,
            'realTraineeProfileId' => $this->realTraineeProfileId,
        ];
    }
}
