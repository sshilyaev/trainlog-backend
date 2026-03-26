<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Enum\ApiError;
use App\Api\ApiException;
use App\Repository\CoachTraineeLinkRepository;
use App\Service\ProfileAccessChecker;

final class CardSummaryAppService
{
    public function __construct(
        private readonly ProfileAppService $profileAppService,
        private readonly VisitAppService $visitAppService,
        private readonly EventAppService $eventAppService,
        private readonly MembershipAppService $membershipAppService,
        private readonly ProfileAccessChecker $profileAccessChecker,
        private readonly CoachTraineeLinkRepository $linkRepository,
        private readonly CalendarAppService $calendarAppService,
    ) {
    }

    /**
     * All data for trainee card: profile, visits, events, memberships.
     * Only coach who has a link to this trainee can call.
     *
     * @return array{profile: array<string, mixed>, visits: list<array<string, mixed>>, events: list<array<string, mixed>>, memberships: list<array<string, mixed>>}
     */
    public function getCardSummary(
        string $coachProfileId,
        string $traineeProfileId,
        string $userId,
        ?string $calendarFrom = null,
        ?string $calendarTo = null
    ): array {
        if (!$this->profileAccessChecker->canAccess($coachProfileId, $userId)) {
            throw new ApiException(ApiError::CoachProfileNotFound);
        }
        if (!$this->linkRepository->existsLink($coachProfileId, $traineeProfileId)) {
            throw new ApiException(ApiError::LinkNotFound);
        }
        if (!$this->profileAccessChecker->canAccess($traineeProfileId, $userId)) {
            throw new ApiException(ApiError::ProfileNotFound);
        }

        $from = $calendarFrom;
        $to = $calendarTo;
        if ($from === null || $from === '' || $to === null || $to === '') {
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $from = $now->modify('first day of this month')->format('Y-m-d');
            $to = $now->modify('last day of this month')->format('Y-m-d');
        }

        $profile = $this->profileAppService->get($traineeProfileId, $userId);
        $calendar = $this->calendarAppService->getCalendar($coachProfileId, $traineeProfileId, $from, $to, $userId);
        $membershipsResult = $this->membershipAppService->list($coachProfileId, $traineeProfileId, $userId);

        return [
            'profile' => $profile,
            'visits' => $calendar['visits'],
            'events' => $calendar['events'],
            'memberships' => $membershipsResult['memberships'],
        ];
    }
}
