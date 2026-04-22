<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Api\ApiException;
use App\Entity\CoachTraineeLink;
use App\Entity\Membership;
use App\Entity\Visit;
use App\Enum\ApiError;
use App\Repository\CoachTraineeLinkRepository;
use App\Repository\MembershipRepository;
use App\Repository\VisitRepository;
use App\Service\ProfileAccessChecker;

final class CoachOverviewAppService
{
    public function __construct(
        private readonly CoachTraineeLinkRepository $linkRepository,
        private readonly MembershipRepository $membershipRepository,
        private readonly VisitRepository $visitRepository,
        private readonly ProfileAccessChecker $profileAccessChecker,
        private readonly ProfileAppService $profileAppService,
    ) {
    }

    /**
     * @return array{
     *   trainees: list<array<string, mixed>>,
     *   week: array{clientsWithDoneVisits: int, oneOffVisits: int, subscriptionVisits: int, range: array{from: string, to: string}},
     *   meta: array{generatedAt: string, page: int, limit: int, total: int, hasNextPage: bool}
     * }
     */
    public function listCoachTraineesOverview(
        string $coachProfileId,
        string $userId,
        bool $includeArchived,
        int $page,
        int $limit
    ): array {
        if (!$this->profileAccessChecker->canAccess($coachProfileId, $userId)) {
            throw new ApiException(ApiError::ProfileNotFound);
        }

        $allLinks = $this->linkRepository->findByCoachProfileId($coachProfileId);
        if (!$includeArchived) {
            $allLinks = array_values(array_filter($allLinks, static fn (CoachTraineeLink $l) => !$l->isArchived()));
        }

        $total = count($allLinks);
        $offset = max(0, ($page - 1) * $limit);
        $links = array_slice($allLinks, $offset, $limit);
        if ($links === []) {
            return [
                'trainees' => [],
                'week' => $this->buildWeekSummary($coachProfileId),
                'meta' => [
                    'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'hasNextPage' => false,
                ],
            ];
        }

        $traineeIds = array_values(array_unique(array_map(
            static fn (CoachTraineeLink $link) => $link->getTraineeProfile()->getId(),
            $links
        )));

        $profiles = $this->profileAppService->getManyByIds($traineeIds, $userId);
        $profilesById = [];
        foreach ($profiles as $profile) {
            if (isset($profile['id']) && is_string($profile['id'])) {
                $profilesById[$profile['id']] = $profile;
            }
        }

        $memberships = $this->membershipRepository->findByCoachProfileId($coachProfileId);
        $activeMembershipByTrainee = $this->pickActiveMembershipsByTrainee($memberships);

        $trainees = [];
        foreach ($links as $link) {
            $traineeId = $link->getTraineeProfile()->getId();
            $trainee = [
                'link' => $this->linkToArray($link),
                'profile' => $profilesById[$traineeId] ?? null,
                'activeMembershipSummary' => null,
                'lastVisitSummary' => null,
            ];
            if (isset($activeMembershipByTrainee[$traineeId])) {
                $trainee['activeMembershipSummary'] = $this->membershipSummaryToArray($activeMembershipByTrainee[$traineeId]);
            }
            $trainees[] = $trainee;
        }

        return [
            'trainees' => $trainees,
            'week' => $this->buildWeekSummary($coachProfileId),
            'meta' => [
                'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'hasNextPage' => ($offset + count($links)) < $total,
            ],
        ];
    }

    /**
     * @param list<Membership> $memberships
     * @return array<string, Membership>
     */
    private function pickActiveMembershipsByTrainee(array $memberships): array
    {
        $result = [];
        foreach ($memberships as $membership) {
            if (!$membership->hasRemainingSessions()) {
                continue;
            }
            $traineeId = $membership->getTraineeProfile()->getId();
            if (!isset($result[$traineeId])) {
                $result[$traineeId] = $membership;
            }
        }
        return $result;
    }

    /**
     * @return array{clientsWithDoneVisits: int, oneOffVisits: int, subscriptionVisits: int, range: array{from: string, to: string}}
     */
    private function buildWeekSummary(string $coachProfileId): array
    {
        $calendar = new \DateTimeImmutable('today');
        $start = $calendar->modify('monday this week');
        $end = $start->modify('+6 days');
        $visits = $this->visitRepository->findByCoachInPeriod($coachProfileId, $start, $end);
        $visitedTrainees = [];
        $oneOff = 0;
        $subscription = 0;
        foreach ($visits as $visit) {
            if ($visit->getStatus() !== Visit::STATUS_DONE) {
                continue;
            }
            $visitedTrainees[$visit->getTraineeProfile()->getId()] = true;
            if ($visit->getMembership() !== null) {
                $subscription++;
            } else {
                $oneOff++;
            }
        }
        return [
            'clientsWithDoneVisits' => count($visitedTrainees),
            'oneOffVisits' => $oneOff,
            'subscriptionVisits' => $subscription,
            'range' => [
                'from' => $start->format('Y-m-d'),
                'to' => $end->format('Y-m-d'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function linkToArray(CoachTraineeLink $link): array
    {
        return [
            'id' => $link->getId(),
            'coachProfileId' => $link->getCoachProfile()->getId(),
            'traineeProfileId' => $link->getTraineeProfile()->getId(),
            'displayName' => $link->getDisplayName(),
            'note' => $link->getNote(),
            'archived' => $link->isArchived(),
            'favorite' => $link->isFavorite(),
            'createdAt' => $link->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    private function membershipSummaryToArray(Membership $m): array
    {
        return [
            'id' => $m->getId(),
            'kind' => $m->getKind(),
            'status' => $m->getStatus(),
            'totalSessions' => $m->getTotalSessions(),
            'usedSessions' => $m->getUsedSessions(),
            'remainingSessions' => max(0, $m->getTotalSessions() - $m->getUsedSessions()),
            'displayCode' => $m->getDisplayCode(),
            'endDate' => $m->getEffectiveEndDate()?->format('Y-m-d'),
        ];
    }

}
