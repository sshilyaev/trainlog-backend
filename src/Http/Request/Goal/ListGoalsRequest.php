<?php

declare(strict_types=1);

namespace App\Http\Request\Goal;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class ListGoalsRequest
{
    public function __construct(
        #[Assert\NotBlank(message: ValidationMessage::ProfileIdRequired->value)]
        #[Assert\Uuid(message: ValidationMessage::ProfileIdUuid->value)]
        public string $profileId = '',
    ) {
    }
}
