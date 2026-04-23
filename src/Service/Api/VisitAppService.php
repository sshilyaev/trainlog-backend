<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Api\ApiException;
use App\Entity\Membership;
use App\Entity\Visit;
use App\Enum\ApiError;
use App\Repository\MembershipRepository;
use App\Repository\ProfileRepository;
use App\Repository\VisitRepository;
use App\Service\ProfileAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class VisitAppService
{
    private const IDEMPOTENCY_TTL_SECONDS = 86400; // 24 hours

    public function __construct(
        private readonly VisitRepository $visitRepository,
        private readonly MembershipRepository $membershipRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly ProfileAccessChecker $profileAccessChecker,
        private readonly EntityManagerInterface $em,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @return array{visits: list<array<string, mixed>>, nextCursor?: string|null}
     */
    public function list(?string $coachProfileId, ?string $traineeProfileId, ?string $month, string $userId, ?int $limit = null, ?string $after = null): array
    {
        if ($coachProfileId !== null && $traineeProfileId !== null) {
            if (!$this->profileAccessChecker->canAccess($coachProfileId, $userId)) {
                throw new ApiException(ApiError::ProfileNotFound);
            }
            if ($limit !== null && $limit > 0) {
                [$visits, $nextCursor] = $this->visitRepository->findPaginated($coachProfileId, $traineeProfileId, $month, min($limit, 200), $after);
                return [
                    'visits' => array_map([$this, 'visitToArray'], $visits),
                    'nextCursor' => $nextCursor,
                ];
            }
            $visits = $this->visitRepository->findByCoachAndTrainee($coachProfileId, $traineeProfileId, $month);
        } elseif ($traineeProfileId !== null) {
            if (!$this->profileAccessChecker->canAccess($traineeProfileId, $userId)) {
                throw new ApiException(ApiError::ProfileNotFound);
            }
            if ($limit !== null && $limit > 0) {
                [$visits, $nextCursor] = $this->visitRepository->findPaginated(null, $traineeProfileId, $month, min($limit, 200), $after);
                return [
                    'visits' => array_map([$this, 'visitToArray'], $visits),
                    'nextCursor' => $nextCursor,
                ];
            }
            $visits = $this->visitRepository->findByTraineeProfileId($traineeProfileId, $month);
        } else {
            throw new ApiException(ApiError::VisitsQueryParamsRequired);
        }
        return ['visits' => array_map([$this, 'visitToArray'], $visits)];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{visit: array<string, mixed>, created: bool}
     */
    public function create(string $userId, array $data): array
    {
        $coachProfileId = $data['coachProfileId'] ?? null;
        $traineeProfileId = $data['traineeProfileId'] ?? null;
        $dateStr = $data['date'] ?? '';
        if ($coachProfileId === null || $traineeProfileId === null || $dateStr === '') {
            throw new ApiException(ApiError::VisitsCreateParamsRequired);
        }
        if (!$this->profileAccessChecker->canAccess($coachProfileId, $userId)) {
            throw new ApiException(ApiError::CoachProfileNotFound);
        }
        $coachProfile = $this->profileRepository->find($coachProfileId);
        $traineeProfile = $this->profileRepository->find($traineeProfileId);
        if ($coachProfile === null || $traineeProfile === null) {
            throw new ApiException(ApiError::ProfileNotFound);
        }
        try {
            $date = new \DateTimeImmutable($dateStr);
        } catch (\Throwable) {
            throw new ApiException(ApiError::InvalidDateFormat);
        }
        $idempotencyKey = isset($data['idempotencyKey']) && (string) $data['idempotencyKey'] !== ''
            ? (string) $data['idempotencyKey']
            : null;

        if ($idempotencyKey !== null) {
            $cacheKey = 'idempotency_visit_' . $userId . '_' . md5($idempotencyKey);
            $cached = $this->cache->get($cacheKey, function (ItemInterface $item) use ($userId, $data, $coachProfile, $traineeProfile, $date) {
                $item->expiresAfter(self::IDEMPOTENCY_TTL_SECONDS);
                $result = $this->performCreateVisit($userId, $data, $coachProfile, $traineeProfile, $date);
                return ['visit' => $result['visit'], 'created' => $result['created']];
            });
            return [
                'visit' => $cached['visit'],
                'created' => $cached['created'],
            ];
        }

        $result = $this->performCreateVisit($userId, $data, $coachProfile, $traineeProfile, $date);
        return $result;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{visit: array<string, mixed>, created: bool}
     */
    private function performCreateVisit(
        string $userId,
        array $data,
        \App\Entity\Profile $coachProfile,
        \App\Entity\Profile $traineeProfile,
        \DateTimeImmutable $date
    ): array {
        $coachProfileId = $coachProfile->getId();
        $traineeProfileId = $traineeProfile->getId();
        $paymentStatus = isset($data['paymentStatus']) ? (string) $data['paymentStatus'] : null;
        $membershipId = isset($data['membershipId']) && $data['membershipId'] !== '' ? (string) $data['membershipId'] : null;

        $visit = (new Visit())
            ->setCoachProfile($coachProfile)
            ->setTraineeProfile($traineeProfile)
            ->setDate($date)
            ->setStatus(Visit::STATUS_DONE);

        if ($paymentStatus === 'paid' && $membershipId !== null) {
            $membership = $this->membershipRepository->findOneById($membershipId);
            if ($membership !== null
                && $membership->getCoachProfile()->getId() === $coachProfileId
                && $membership->getTraineeProfile()->getId() === $traineeProfileId
                && $membership->hasRemainingSessions()
            ) {
                $membership->setUsedSessions($membership->getUsedSessions() + 1);
                $visit->setMembership($membership)
                    ->setMembershipDisplayCode($membership->getDisplayCode())
                    ->setPaymentStatus(Visit::PAYMENT_PAID);
                if (
                    $membership->getKind() === Membership::KIND_BY_VISITS
                    && $membership->getUsedSessions() >= $membership->getTotalSessions()
                ) {
                    $membership->setStatus(Membership::STATUS_FINISHED);
                }
            } else {
                $visit->setPaymentStatus(Visit::PAYMENT_DEBT);
            }
        } elseif ($paymentStatus === 'paid' && $membershipId === null) {
            $visit->setPaymentStatus(Visit::PAYMENT_PAID);
        } else {
            $activeMemberships = $this->membershipRepository->findActiveWithRemainderForPair($coachProfileId, $traineeProfileId);
            if (\count($activeMemberships) > 0 && $paymentStatus !== 'debt') {
                $membership = $activeMemberships[0];
                $membership->setUsedSessions($membership->getUsedSessions() + 1);
                $visit->setMembership($membership)
                    ->setMembershipDisplayCode($membership->getDisplayCode())
                    ->setPaymentStatus(Visit::PAYMENT_PAID);
                if (
                    $membership->getKind() === Membership::KIND_BY_VISITS
                    && $membership->getUsedSessions() >= $membership->getTotalSessions()
                ) {
                    $membership->setStatus(Membership::STATUS_FINISHED);
                }
            } else {
                $visit->setPaymentStatus(Visit::PAYMENT_DEBT);
            }
        }
        $this->em->persist($visit);
        $this->em->flush();
        return ['visit' => $this->visitToArray($visit), 'created' => true];
    }

    /** @return array<string, mixed> */
    public function get(string $id, string $userId): array
    {
        $visit = $this->visitRepository->findOneById($id);
        if ($visit === null || !$this->canAccessVisit($visit, $userId)) {
            throw new ApiException(ApiError::VisitNotFound);
        }
        return $this->visitToArray($visit);
    }

    /** @param array<string, mixed> $data */
    public function update(string $id, string $userId, array $data): array
    {
        $visit = $this->visitRepository->findOneById($id);
        if ($visit === null) {
            throw new ApiException(ApiError::VisitNotFound);
        }
        if ($visit->getCoachProfile()->getUserId() !== $userId) {
            throw new ApiException(ApiError::OnlyCoachCanUpdateVisit);
        }
        if (isset($data['status']) && $data['status'] === Visit::STATUS_CANCELLED) {
            $membership = $visit->getMembership();
            if ($membership !== null) {
                $membership->setUsedSessions(max(0, $membership->getUsedSessions() - 1));
                if ($membership->getStatus() === Membership::STATUS_FINISHED && $membership->getUsedSessions() < $membership->getTotalSessions()) {
                    $membership->setStatus(Membership::STATUS_ACTIVE);
                }
            }
            $visit->setStatus(Visit::STATUS_CANCELLED)
                ->setPaymentStatus(Visit::PAYMENT_UNPAID)
                ->setCancelledAt(new \DateTimeImmutable())
                ->setMembership(null)
                ->setMembershipDisplayCode(null);
            $this->em->flush();
            return $this->visitToArray($visit);
        }
        if (isset($data['paymentStatus']) && $data['paymentStatus'] === Visit::PAYMENT_PAID
            && (!isset($data['membershipId']) || $data['membershipId'] === null || $data['membershipId'] === '')
            && $visit->getPaymentStatus() === Visit::PAYMENT_DEBT
        ) {
            $visit->setPaymentStatus(Visit::PAYMENT_PAID)
                ->setMembership(null)
                ->setMembershipDisplayCode(null);
            $this->em->flush();
            return $this->visitToArray($visit);
        }
        if (isset($data['membershipId']) && $visit->getPaymentStatus() === Visit::PAYMENT_DEBT) {
            $membership = $this->membershipRepository->findOneById((string) $data['membershipId']);
            if ($membership === null) {
                throw new ApiException(ApiError::MembershipNotFound);
            }
            if ($membership->getCoachProfile()->getId() !== $visit->getCoachProfile()->getId()
                || $membership->getTraineeProfile()->getId() !== $visit->getTraineeProfile()->getId()) {
                throw new ApiException(ApiError::MembershipDoesNotMatchVisit);
            }
            if (!$membership->hasRemainingSessions()) {
                throw new ApiException(ApiError::MembershipNoRemainingSessions);
            }
            $membership->setUsedSessions($membership->getUsedSessions() + 1);
            $visit->setMembership($membership)
                ->setMembershipDisplayCode($membership->getDisplayCode())
                ->setPaymentStatus(Visit::PAYMENT_PAID);
            if (
                $membership->getKind() === Membership::KIND_BY_VISITS
                && $membership->getUsedSessions() >= $membership->getTotalSessions()
            ) {
                $membership->setStatus(Membership::STATUS_FINISHED);
            }
            $this->em->flush();
            return $this->visitToArray($visit);
        }
        throw new ApiException(ApiError::VisitUpdateNoValidAction);
    }

    private function canAccessVisit(Visit $v, string $userId): bool
    {
        return $v->getCoachProfile()->getUserId() === $userId
            || $v->getTraineeProfile()->getUserId() === $userId;
    }

    /** @return array<string, mixed> */
    public function visitToArrayPublic(Visit $v): array
    {
        return $this->visitToArray($v);
    }

    /** @return array<string, mixed> */
    private function visitToArray(Visit $v): array
    {
        return [
            'id' => $v->getId(),
            'coachProfileId' => $v->getCoachProfile()->getId(),
            'traineeProfileId' => $v->getTraineeProfile()->getId(),
            'date' => $v->getDate()->format('Y-m-d'),
            'status' => $v->getStatus(),
            'paymentStatus' => $v->getPaymentStatus(),
            'membershipId' => $v->getMembership()?->getId(),
            'membershipDisplayCode' => $v->getMembershipDisplayCode(),
            'cancelledAt' => $v->getCancelledAt()?->format(\DateTimeInterface::ATOM),
            'createdAt' => $v->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
