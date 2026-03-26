<?php

declare(strict_types=1);

namespace App\Http\Request\Event;

use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateEventRequest
{
    #[Assert\Length(max: 255, maxMessage: 'title не длиннее 255 символов')]
    public ?string $title = null;

    #[Assert\Date(message: ValidationMessage::DateFormat->value)]
    public ?string $date = null;

    public ?string $description = null;
    public ?bool $remind = null;
    #[Assert\Length(max: 12)]
    public ?string $colorHex = null;
    public ?bool $isCancelled = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'date' => $this->date,
            'description' => $this->description,
            'remind' => $this->remind,
            'colorHex' => $this->colorHex,
            'isCancelled' => $this->isCancelled,
        ], static fn ($v) => $v !== null);
    }
}
