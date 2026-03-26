<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Enum\ApiError;
use App\Api\ApiException;
use App\Repository\EventRepository;
use App\Repository\VisitRepository;
use App\Service\ProfileAccessChecker;

final class CalendarAppService
{
    public function __construct(
        private readonly VisitRepository $visitRepository,
        private readonly EventRepository $eventRepository,
        private readonly ProfileAccessChecker $profileAccessChecker,
        private readonly VisitAppService $visitAppService,
        private readonly EventAppService $eventAppService,
    ) {
    }

    /**
     * Combined calendar feed: visits + events for coach–trainee pair in date range.
     *
     * @return array{visits: list<array<string, mixed>>, events: list<array<string, mixed>>}
     */
    public function getCalendar(string $coachProfileId, string $traineeProfileId, string $from, string $to, string $userId): array
    {
        if (!$this->profileAccessChecker->canAccess($coachProfileId, $userId)
            || !$this->profileAccessChecker->canAccess($traineeProfileId, $userId)) {
            throw new ApiException(ApiError::ProfileNotFound);
        }
        $visits = $this->visitRepository->findByCoachAndTraineeInPeriod($coachProfileId, $traineeProfileId, $from, $to);
        $events = $this->eventRepository->findByCoachAndTraineeInPeriod($coachProfileId, $traineeProfileId, $from, $to);
        return [
            'visits' => array_map([$this->visitAppService, 'visitToArrayPublic'], $visits),
            'events' => array_map([$this->eventAppService, 'eventToArrayPublic'], $events),
        ];
    }
}
