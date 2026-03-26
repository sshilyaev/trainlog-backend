<?php

declare(strict_types=1);

namespace App\Http\Request\CoachOverview;

use Symfony\Component\Validator\Constraints as Assert;

final class ListCoachTraineesOverviewRequest
{
    public function __construct(
        #[Assert\Choice(choices: ['weekStats', 'activeMembership', 'lastVisit'], multiple: true)]
        public array $include = [],
        #[Assert\Regex(pattern: '/^\d{4}-\d{2}$/')]
        public ?string $month = null,
        public bool $includeArchived = true,
        #[Assert\Positive]
        public int $page = 1,
        #[Assert\Range(min: 1, max: 200)]
        public int $limit = 200,
    ) {
    }
}
