<?php

declare(strict_types=1);

namespace App\Http\Request\Calendar;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class CalendarFeedRequest
{
    public function __construct(
        #[Assert\NotBlank(message: ValidationMessage::CoachProfileIdRequired->value)]
        #[Assert\Uuid(message: ValidationMessage::CoachProfileIdUuid->value)]
        public string $coachProfileId = '',

        #[Assert\NotBlank(message: ValidationMessage::TraineeProfileIdRequired->value)]
        #[Assert\Uuid(message: ValidationMessage::TraineeProfileIdUuid->value)]
        public string $traineeProfileId = '',

        #[Assert\NotBlank(message: 'Укажите from (YYYY-MM-DD)')]
        #[Assert\Date(message: ValidationMessage::DateFormat->value)]
        public string $from = '',

        #[Assert\NotBlank(message: 'Укажите to (YYYY-MM-DD)')]
        #[Assert\Date(message: ValidationMessage::DateFormat->value)]
        public string $to = '',
    ) {
    }
}
