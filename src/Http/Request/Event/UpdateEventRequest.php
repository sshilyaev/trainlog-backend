<?php

declare(strict_types=1);

namespace App\Http\Request\Event;

use App\Entity\Event;
use App\Enum\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateEventRequest
{
    #[Assert\Length(max: 255, maxMessage: 'title не длиннее 255 символов')]
    public ?string $title = null;

    #[Assert\Date(message: ValidationMessage::DateFormat->value)]
    public ?string $date = null;

    #[Assert\Choice(choices: [Event::MODE_DATE, Event::MODE_PERIOD])]
    public ?string $mode = null;

    #[Assert\Date(message: ValidationMessage::DateFormat->value)]
    public ?string $periodStart = null;

    #[Assert\Date(message: ValidationMessage::DateFormat->value)]
    public ?string $periodEnd = null;

    #[Assert\Choice(choices: [Event::TYPE_VACATION, Event::TYPE_SICK])]
    public ?string $periodType = null;

    public ?bool $freezeMembership = null;

    public ?string $description = null;
    public ?bool $remind = null;
    #[Assert\Length(max: 12)]
    public ?string $colorHex = null;
    #[Assert\Choice(choices: [Event::TYPE_GENERAL, Event::TYPE_WORKOUT, Event::TYPE_MEASUREMENT, Event::TYPE_NUTRITION, Event::TYPE_REMINDER, Event::TYPE_VACATION, Event::TYPE_SICK])]
    public ?string $eventType = null;
    public ?bool $isCancelled = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'date' => $this->date,
            'mode' => $this->mode,
            'periodStart' => $this->periodStart,
            'periodEnd' => $this->periodEnd,
            'periodType' => $this->periodType,
            'freezeMembership' => $this->freezeMembership,
            'description' => $this->description,
            'remind' => $this->remind,
            'colorHex' => $this->colorHex,
            'eventType' => $this->eventType,
            'isCancelled' => $this->isCancelled,
        ], static fn ($v) => $v !== null);
    }
}
