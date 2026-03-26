<?php

declare(strict_types=1);

namespace App\Http\Request\Visit;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class ListVisitsRequest
{
    public function __construct(
        public ?string $coachProfileId = null,
        public ?string $traineeProfileId = null,
        #[Assert\Regex(pattern: '#^\d{4}-\d{2}$#', message: ValidationMessage::MonthFormat->value)]
        public ?string $month = null,
        /** Limit (default 50, max 200). If set, enables cursor pagination. */
        public ?int $limit = null,
        /** Cursor from previous page (id of last item). */
        public ?string $after = null,
    ) {
    }
}
