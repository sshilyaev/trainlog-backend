<?php

declare(strict_types=1);

namespace App\Http\Request\CoachTraineeLink;

final class UpdateCoachTraineeLinkRequest
{
    public ?string $displayName = null;
    public ?string $note = null;
    public ?bool $archived = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'displayName' => $this->displayName,
            'note' => $this->note,
            'archived' => $this->archived,
        ], static fn ($v) => $v !== null);
    }
}
