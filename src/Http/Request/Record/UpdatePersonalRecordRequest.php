<?php

declare(strict_types=1);

namespace App\Http\Request\Record;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdatePersonalRecordRequest
{
    #[Assert\Date(message: 'Неверный формат даты (используйте ГГГГ-ММ-ДД)')]
    public ?string $recordDate = null;

    #[Assert\Choice(choices: ['catalog', 'custom'], message: 'sourceType должен быть catalog или custom')]
    public ?string $sourceType = null;

    public ?string $activitySlug = null;
    public ?string $activityName = null;
    public ?string $activityType = null;
    public ?string $notes = null;

    /** @var array<int, array{metricType?: string, value?: float|int|string, unit?: string}>|null */
    public ?array $metrics = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'recordDate' => $this->recordDate,
            'sourceType' => $this->sourceType,
            'activitySlug' => $this->activitySlug,
            'activityName' => $this->activityName,
            'activityType' => $this->activityType,
            'notes' => $this->notes,
            'metrics' => $this->metrics,
        ], static fn ($v) => $v !== null);
    }
}
