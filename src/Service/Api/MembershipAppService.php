<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Enum\ApiError;
use App\Api\ApiException;
use App\Entity\Membership;
use App\Repository\CoachTraineeLinkRepository;
use App\Repository\MembershipRepository;
use App\Repository\ProfileRepository;
use App\Service\ProfileAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class MembershipAppService
{
    public function __construct(
        private readonly MembershipRepository $membershipRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly CoachTraineeLinkRepository $linkRepository,
        private readonly ProfileAccessChecker $profileAccessChecker,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /** @return array{memberships: list<array<string, mixed>>} */
    public function list(?string $coachProfileId, ?string $traineeProfileId, string $userId): array
    {
        if ($coachProfileId !== null && $coachProfileId !== '') {
            if (!$this->profileAccessChecker->canAccess($coachProfileId, $userId)) {
                throw new ApiException(ApiError::ProfileNotFound);
            }
            $memberships = $this->membershipRepository->findByCoachProfileId($coachProfileId, $traineeProfileId ?: null);
        } elseif ($traineeProfileId !== null && $traineeProfileId !== '') {
            if (!$this->profileAccessChecker->canAccess($traineeProfileId, $userId)) {
                throw new ApiException(ApiError::ProfileNotFound);
            }
            $memberships = $this->membershipRepository->findByTraineeProfileId($traineeProfileId);
        } else {
            throw new ApiException(ApiError::CoachOrTraineeProfileIdRequired);
        }
        return ['memberships' => array_map([$this, 'membershipToArray'], $memberships)];
    }

    /** @param array<string, mixed> $data */
    public function create(string $userId, array $data): array
    {
        $coachProfileId = $data['coachProfileId'] ?? null;
        $traineeProfileId = $data['traineeProfileId'] ?? null;
        if ($coachProfileId === null || $traineeProfileId === null) {
            throw new ApiException(ApiError::CoachAndTraineeProfileIdRequired);
        }
        if (!$this->profileAccessChecker->canAccess($coachProfileId, $userId)) {
            throw new ApiException(ApiError::CoachProfileNotFound);
        }
        $coachProfile = $this->profileRepository->find($coachProfileId);
        if ($coachProfile === null || $coachProfile->getType() !== 'coach') {
            throw new ApiException(ApiError::ProfileMustBeCoach);
        }
        $traineeProfile = $this->profileRepository->find($traineeProfileId);
        if ($traineeProfile === null || $traineeProfile->getType() !== 'trainee') {
            throw new ApiException(ApiError::TraineeProfileNotFound);
        }
        if (!$this->linkRepository->existsLink($coachProfileId, $traineeProfileId)) {
            throw new ApiException(ApiError::CoachAndTraineeMustBeLinked);
        }
        $kind = isset($data['kind']) && $data['kind'] === Membership::KIND_UNLIMITED
            ? Membership::KIND_UNLIMITED
            : Membership::KIND_BY_VISITS;
        $totalSessions = $kind === Membership::KIND_UNLIMITED ? 0 : (int) ($data['totalSessions'] ?? 0);
        if ($kind === Membership::KIND_BY_VISITS && $totalSessions < 1) {
            throw new ApiException(ApiError::TotalSessionsAtLeast1);
        }
        $m = (new Membership())
            ->setCoachProfile($coachProfile)
            ->setTraineeProfile($traineeProfile)
            ->setKind($kind)
            ->setTotalSessions($totalSessions)
            ->setUsedSessions(0)
            ->setPriceRub(isset($data['priceRub']) ? (int) $data['priceRub'] : null)
            ->setStatus(Membership::STATUS_ACTIVE)
            ->setDisplayCode($this->membershipRepository->getNextDisplayCodeForTrainee($coachProfileId, $traineeProfileId, $kind));
        if ($kind === Membership::KIND_UNLIMITED) {
            $startDateStr = $data['startDate'] ?? null;
            $endDateStr = $data['endDate'] ?? null;
            if ($startDateStr === null || $endDateStr === null) {
                throw new ApiException(ApiError::ValidationFailed, null, ['messages' => ['Для безлимитного абонемента укажите startDate и endDate']]);
            }
            try {
                $m->setStartDate(new \DateTimeImmutable($startDateStr));
                $m->setEndDate(new \DateTimeImmutable($endDateStr));
            } catch (\Exception) {
                throw new ApiException(ApiError::InvalidDateFormatShort);
            }
            $m->setFreezeDays((int) ($data['freezeDays'] ?? 0));
        }
        $errors = $this->validator->validate($m);
        if (count($errors) > 0) {
            throw new ApiException(ApiError::ValidationFailed, null, [
                'messages' => array_map(fn ($e) => $e->getMessage(), iterator_to_array($errors)),
            ]);
        }
        $this->em->persist($m);
        $this->em->flush();
        return $this->membershipToArray($m);
    }

    /** @return array<string, mixed> */
    public function get(string $id, string $userId): array
    {
        $m = $this->membershipRepository->findOneById($id);
        if ($m === null || !$this->canAccessMembership($m, $userId)) {
            throw new ApiException(ApiError::MembershipNotFound);
        }
        return $this->membershipToArray($m);
    }

    /** @param array<string, mixed> $data */
    public function update(string $id, string $userId, array $data): array
    {
        $m = $this->membershipRepository->findOneById($id);
        if ($m === null) {
            throw new ApiException(ApiError::MembershipNotFound);
        }
        if ($m->getCoachProfile()->getUserId() !== $userId) {
            throw new ApiException(ApiError::OnlyCoachCanUpdateMembership);
        }
        if (array_key_exists('status', $data)) {
            $status = (string) $data['status'];
            if (\in_array($status, [Membership::STATUS_ACTIVE, Membership::STATUS_FINISHED, Membership::STATUS_CANCELLED], true)) {
                $m->setStatus($status);
            }
        }
        if (array_key_exists('freezeDays', $data)) {
            $freezeDays = (int) $data['freezeDays'];
            if ($freezeDays < 0) {
                throw new ApiException(ApiError::InvalidFreezeDays);
            }
            if ($m->getKind() === Membership::KIND_UNLIMITED) {
                $m->setFreezeDays($freezeDays);
            } else {
                // by_visits memberships do not support freeze; keep stored value at 0
                $m->setFreezeDays(0);
            }
        }
        $this->em->flush();
        return $this->membershipToArray($m);
    }

    private function canAccessMembership(Membership $m, string $userId): bool
    {
        return $m->getCoachProfile()->getUserId() === $userId
            || $m->getTraineeProfile()->getUserId() === $userId;
    }

    /** @return array<string, mixed> */
    private function membershipToArray(Membership $m): array
    {
        $out = [
            'id' => $m->getId(),
            'coachProfileId' => $m->getCoachProfile()->getId(),
            'traineeProfileId' => $m->getTraineeProfile()->getId(),
            'kind' => $m->getKind(),
            'totalSessions' => $m->getTotalSessions(),
            'usedSessions' => $m->getUsedSessions(),
            'priceRub' => $m->getPriceRub(),
            'status' => $m->getStatus(),
            'displayCode' => $m->getDisplayCode(),
            'createdAt' => $m->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
        if ($m->getKind() === Membership::KIND_UNLIMITED) {
            $out['startDate'] = $m->getStartDate()?->format('Y-m-d');
            $out['endDate'] = $m->getEndDate()?->format('Y-m-d');
            $out['freezeDays'] = $m->getFreezeDays();
        }
        return $out;
    }
}
