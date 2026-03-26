<?php

declare(strict_types=1);

namespace App\Http\Request\Event;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateEventRequest
{
    #[Assert\NotBlank(message: ValidationMessage::CoachProfileIdRequired->value)]
    #[Assert\Uuid(message: ValidationMessage::CoachProfileIdUuid->value)]
    public string $coachProfileId = '';

    #[Assert\NotBlank(message: ValidationMessage::TraineeProfileIdRequired->value)]
    #[Assert\Uuid(message: ValidationMessage::TraineeProfileIdUuid->value)]
    public string $traineeProfileId = '';

    #[Assert\NotBlank(message: 'Укажите title')]
    #[Assert\Length(max: 255, maxMessage: 'title не длиннее 255 символов')]
    public string $title = '';

    #[Assert\NotBlank(message: ValidationMessage::DateRequired->value)]
    #[Assert\Date(message: ValidationMessage::DateFormat->value)]
    public string $date = '';

    public ?string $description = null;
    public bool $remind = false;
    #[Assert\Length(max: 12)]
    public ?string $colorHex = null;

    /** Idempotency key for safe retry (optional). */
    public ?string $idempotencyKey = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'coachProfileId' => $this->coachProfileId,
            'traineeProfileId' => $this->traineeProfileId,
            'title' => $this->title,
            'date' => $this->date,
            'description' => $this->description,
            'remind' => $this->remind,
            'colorHex' => $this->colorHex,
        ];
        if ($this->idempotencyKey !== null && $this->idempotencyKey !== '') {
            $data['idempotencyKey'] = $this->idempotencyKey;
        }
        return $data;
    }
}
