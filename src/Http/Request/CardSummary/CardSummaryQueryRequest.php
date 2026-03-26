<?php

declare(strict_types=1);

namespace App\Http\Request\CardSummary;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** Query-параметры для GET card-summary (опциональны — по умолчанию текущий месяц). */
final class CardSummaryQueryRequest
{
    public function __construct(
        #[Assert\Date(message: ValidationMessage::DateFormat->value)]
        public ?string $calendarFrom = null,

        #[Assert\Date(message: ValidationMessage::DateFormat->value)]
        public ?string $calendarTo = null,
    ) {
    }
}
