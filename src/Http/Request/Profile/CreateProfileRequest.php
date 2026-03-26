<?php

declare(strict_types=1);

namespace App\Http\Request\Profile;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateProfileRequest
{
    #[Assert\NotBlank(message: ValidationMessage::TypeRequired->value)]
    #[Assert\Choice(choices: ['coach', 'trainee'], message: ValidationMessage::TypeCoachOrTrainee->value)]
    public string $type = '';

    #[Assert\NotBlank(message: ValidationMessage::NameRequired->value)]
    public string $name = '';

    public ?string $gymName = null;
    public ?string $gender = null;
    public ?string $iconEmoji = null;
    public ?string $ownerCoachProfileId = null;
    #[Assert\Positive(message: 'weight должен быть > 0')]
    public ?float $weight = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
            'name' => $this->name,
            'gymName' => $this->gymName,
            'gender' => $this->gender,
            'iconEmoji' => $this->iconEmoji,
        ];
        if ($this->ownerCoachProfileId !== null && $this->ownerCoachProfileId !== '') {
            $data['ownerCoachProfileId'] = $this->ownerCoachProfileId;
        }
        if ($this->weight !== null) {
            $data['weight'] = $this->weight;
        }
        return $data;
    }
}
