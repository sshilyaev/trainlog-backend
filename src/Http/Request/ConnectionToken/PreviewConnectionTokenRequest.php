<?php

declare(strict_types=1);

namespace App\Http\Request\ConnectionToken;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class PreviewConnectionTokenRequest
{
    public function __construct(
        #[Assert\NotBlank(message: ValidationMessage::TokenQueryRequired->value)]
        public string $token = '',
    ) {
    }
}
