<?php

declare(strict_types=1);

namespace App\Http\Request\Support;

use Symfony\Component\Validator\Constraints as Assert;

final class ClaimSupportRewardRequest
{
    #[Assert\NotBlank(message: 'Укажите adProvider')]
    #[Assert\Length(max: 32)]
    public string $adProvider = '';

    #[Assert\NotBlank(message: 'Укажите externalEventId')]
    #[Assert\Length(max: 191)]
    public string $externalEventId = '';

    #[Assert\NotBlank(message: 'Укажите rewardValueKg')]
    #[Assert\GreaterThan(value: 0, message: 'rewardValueKg должен быть больше 0')]
    #[Assert\LessThanOrEqual(value: 5, message: 'rewardValueKg не должен превышать 5')]
    public float $rewardValueKg = 1.0;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'adProvider' => $this->adProvider,
            'externalEventId' => $this->externalEventId,
            'rewardValueKg' => $this->rewardValueKg,
        ];
    }
}

