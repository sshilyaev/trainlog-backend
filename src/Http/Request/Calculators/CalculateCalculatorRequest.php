<?php

declare(strict_types=1);

namespace App\Http\Request\Calculators;

use Symfony\Component\Validator\Constraints as Assert;

final class CalculateCalculatorRequest
{
    #[Assert\Uuid(message: 'profileId должен быть UUID')]
    public ?string $profileId = null;

    /**
     * @var array<string, int|float|string>
     */
    #[Assert\Type('array')]
    public array $inputs = [];
}

