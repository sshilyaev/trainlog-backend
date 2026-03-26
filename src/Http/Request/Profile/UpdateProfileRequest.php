<?php

declare(strict_types=1);

namespace App\Http\Request\Profile;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateProfileRequest
{
    public ?string $name = null;
    public ?string $gymName = null;
    public ?string $gender = null;
    public ?string $iconEmoji = null;
    public ?bool $developerMode = null;
    public ?string $dateOfBirth = null;
    public ?string $phoneNumber = null;
    public ?string $telegramUsername = null;
    public ?string $notes = null;
    public ?float $height = null;
    #[Assert\Positive(message: 'weight должен быть > 0')]
    public ?float $weight = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'gymName' => $this->gymName,
            'gender' => $this->gender,
            'iconEmoji' => $this->iconEmoji,
            'developerMode' => $this->developerMode,
            'dateOfBirth' => $this->dateOfBirth,
            'phoneNumber' => $this->phoneNumber,
            'telegramUsername' => $this->telegramUsername,
            'notes' => $this->notes,
            'height' => $this->height,
            'weight' => $this->weight,
        ];
    }
}
