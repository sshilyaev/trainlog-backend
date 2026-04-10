<?php

declare(strict_types=1);

namespace App\Http\Request\Support;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateSupportCampaignRequest
{
    #[Assert\Choice(choices: ['lose_weight', 'gain_weight'], message: 'goalType должен быть lose_weight или gain_weight')]
    public ?string $goalType = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'goalType' => $this->goalType,
        ];
    }
}

