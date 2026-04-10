<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Api\ApiException;
use App\Entity\Profile;
use App\Enum\ApiError;
use App\Repository\CoachTraineeLinkRepository;
use App\Repository\MembershipRepository;
use App\Repository\ProfileRepository;
use App\Repository\VisitRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class CoachStatisticsAppService
{
    private const CACHE_TTL_SECONDS = 300; // 5 minutes
    private const CACHE_KEY_PREFIX = 'coach_statistics.';

    public function __construct(
        private readonly ProfileRepository $profileRepository,
        private readonly CoachTraineeLinkRepository $coachTraineeLinkRepository,
        private readonly VisitRepository $visitRepository,
        private readonly MembershipRepository $membershipRepository,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * Statistics for coach profile. Only the profile owner can request.
     *
     * @return array{
     *   period: string,
     *   trainees: array{activeCount: int, newThisMonth: int, uniqueWithVisitsInPeriod: int},
     *   visits: array{
     *     thisMonth: int,
     *     previousMonth: int,
     *     total: int,
     *     thisMonthBySubscription: int,
     *     thisMonthOneTimePaid: int,
     *     thisMonthOneTimeDebt: int,
     *     thisMonthCancelled: int,
     *     previousMonthBySubscription: int,
     *     previousMonthOneTimePaid: int,
     *     previousMonthOneTimeDebt: int,
     *     previousMonthCancelled: int
     *   },
     *   memberships: array{activeCount: int, endingSoonCount: int, unlimitedCount: int, byVisitsCount: int, createdInPeriod: int}
     * }
     */
    public function getStatistics(string $coachProfileId, string $month, string $userId, int $months = 1): array
    {
        $profile = $this->profileRepository->find($coachProfileId);
        if ($profile === null || $profile->getType() !== Profile::TYPE_COACH || $profile->getUserId() !== $userId) {
            throw new ApiException(ApiError::ProfileNotFound);
        }

        if (!\in_array($months, [1, 3, 6], true)) {
            $months = 1;
        }

        $cacheKey = self::CACHE_KEY_PREFIX . $coachProfileId . '.' . $month . '.m' . $months;
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($coachProfileId, $month, $months): array {
            $item->expiresAfter(self::CACHE_TTL_SECONDS);
            return $this->computeStatistics($coachProfileId, $month, $months);
        });
    }

    /**
     * @return array{
     *   period: string,
     *   trainees: array{activeCount: int, newThisMonth: int, uniqueWithVisitsInPeriod: int},
     *   visits: array{
     *     thisMonth: int,
     *     previousMonth: int,
     *     total: int,
     *     thisMonthBySubscription: int,
     *     thisMonthOneTimePaid: int,
     *     thisMonthOneTimeDebt: int,
     *     thisMonthCancelled: int,
     *     previousMonthBySubscription: int,
     *     previousMonthOneTimePaid: int,
     *     previousMonthOneTimeDebt: int,
     *     previousMonthCancelled: int
     *   },
     *   memberships: array{activeCount: int, endingSoonCount: int, unlimitedCount: int, byVisitsCount: int, createdInPeriod: int}
     * }
     */
    private function computeStatistics(string $coachProfileId, string $month, int $months): array
    {
        $previousMonth = $this->previousMonth($month);

        [$periodStart, $periodEndExclusive] = $this->periodBounds($month, $months);
        $uniqueWithVisitsInPeriod = $this->visitRepository->countDistinctTraineesWithDoneVisitsInRange(
            $coachProfileId,
            $periodStart,
            $periodEndExclusive
        );
        $membershipsCreatedInPeriod = $this->membershipRepository->countCreatedByCoachProfileIdInRange(
            $coachProfileId,
            $periodStart,
            $periodEndExclusive
        );

        $traineesActiveCount = $this->coachTraineeLinkRepository->countActiveByCoachProfileId($coachProfileId);
        $traineesNewThisMonth = $this->coachTraineeLinkRepository->countNewLinksInMonth($coachProfileId, $month);

        $visitsThisMonth = $this->visitRepository->countByCoachProfileIdInMonth($coachProfileId, $month);
        $visitsPreviousMonth = $this->visitRepository->countByCoachProfileIdInMonth($coachProfileId, $previousMonth);
        $visitsTotal = $this->visitRepository->countTotalByCoachProfileId($coachProfileId);
        $visitsThisMonthByType = $this->visitRepository->countByCoachProfileIdInMonthByType($coachProfileId, $month);
        $visitsPreviousMonthByType = $this->visitRepository->countByCoachProfileIdInMonthByType($coachProfileId, $previousMonth);

        $membershipsActiveCount = $this->membershipRepository->countActiveByCoachProfileId($coachProfileId);
        $membershipsEndingSoonCount = $this->membershipRepository->countEndingSoonByCoachProfileId($coachProfileId);
        $membershipsByKind = $this->membershipRepository->countActiveByCoachProfileIdByKind($coachProfileId);

        return [
            'period' => $month,
            'trainees' => [
                'activeCount' => $traineesActiveCount,
                'newThisMonth' => $traineesNewThisMonth,
                'uniqueWithVisitsInPeriod' => $uniqueWithVisitsInPeriod,
            ],
            'visits' => [
                'thisMonth' => $visitsThisMonth,
                'previousMonth' => $visitsPreviousMonth,
                'total' => $visitsTotal,
                'thisMonthBySubscription' => $visitsThisMonthByType['bySubscription'],
                'thisMonthOneTimePaid' => $visitsThisMonthByType['oneTimePaid'],
                'thisMonthOneTimeDebt' => $visitsThisMonthByType['oneTimeDebt'],
                'thisMonthCancelled' => $visitsThisMonthByType['cancelled'],
                'previousMonthBySubscription' => $visitsPreviousMonthByType['bySubscription'],
                'previousMonthOneTimePaid' => $visitsPreviousMonthByType['oneTimePaid'],
                'previousMonthOneTimeDebt' => $visitsPreviousMonthByType['oneTimeDebt'],
                'previousMonthCancelled' => $visitsPreviousMonthByType['cancelled'],
            ],
            'memberships' => [
                'activeCount' => $membershipsActiveCount,
                'endingSoonCount' => $membershipsEndingSoonCount,
                'unlimitedCount' => $membershipsByKind['unlimited'],
                'byVisitsCount' => $membershipsByKind['byVisits'],
                'createdInPeriod' => $membershipsCreatedInPeriod,
            ],
        ];
    }

    /**
     * Интервал из ровно $months календарных месяцев, заканчивающийся выбранным $yearMonth включительно.
     *
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable} [start inclusive, end exclusive)
     */
    private function periodBounds(string $yearMonth, int $months): array
    {
        $endMonthStart = \DateTimeImmutable::createFromFormat('!Y-m', $yearMonth);
        if ($endMonthStart === false) {
            $endMonthStart = new \DateTimeImmutable('first day of this month');
        }
        $endMonthStart = $endMonthStart->setTime(0, 0);
        $months = max(1, min(6, $months));
        $periodStart = $endMonthStart->modify('-' . ($months - 1) . ' months');
        $periodEndExclusive = $endMonthStart->modify('+1 month');

        return [$periodStart, $periodEndExclusive];
    }

    private function previousMonth(string $yearMonth): string
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m', $yearMonth);
        if ($date === false) {
            return (new \DateTimeImmutable('first day of last month'))->format('Y-m');
        }
        return $date->modify('-1 month')->format('Y-m');
    }
}
