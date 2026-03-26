<?php

declare(strict_types=1);

namespace App\Http\Request\Event;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class ListEventsRequest
{
    public function __construct(
        #[Assert\Uuid(message: ValidationMessage::CoachProfileIdUuid->value)]
        public ?string $coachProfileId = null,

        #[Assert\Uuid(message: ValidationMessage::TraineeProfileIdUuid->value)]
        public ?string $traineeProfileId = null,

        #[Assert\Uuid(message: ValidationMessage::ProfileIdUuid->value)]
        public ?string $profileId = null,

        /** Limit (1–200). If set, enables cursor pagination. */
        public ?int $limit = null,

        /** Cursor from previous page (id of last event). */
        public ?string $after = null,
    ) {
    }
}
