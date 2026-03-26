<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Enum\ApiError;
use App\Api\ApiException;
use App\Entity\Goal;
use App\Repository\GoalRepository;
use App\Repository\ProfileRepository;
use App\Service\ProfileAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class GoalAppService
{
    public function __construct(
        private readonly GoalRepository $goalRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly ProfileAccessChecker $profileAccessChecker,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /** @return array{goals: list<array<string, mixed>>} */
    public function list(string $profileId, string $userId): array
    {
        if ($profileId === '') {
            throw new ApiException(ApiError::ProfileIdQueryRequired);
        }
        if (!$this->profileAccessChecker->canAccess($profileId, $userId)) {
            throw new ApiException(ApiError::ProfileNotFound);
        }
        $goals = $this->goalRepository->findByProfileIdOrderByTargetDate($profileId);
        return ['goals' => array_map([$this, 'goalToArray'], $goals)];
    }

    /** @param array<string, mixed> $data */
    public function create(string $userId, array $data): array
    {
        $profileId = $data['profileId'] ?? null;
        if ($profileId === null || $profileId === '') {
            throw new ApiException(ApiError::ProfileIdRequired);
        }
        $profile = $this->profileRepository->find($profileId);
        if ($profile === null || !$this->profileAccessChecker->canAccess($profileId, $userId)) {
            throw new ApiException(ApiError::ProfileNotFound);
        }
        $targetDateStr = $data['targetDate'] ?? '';
        try {
            $targetDate = new \DateTimeImmutable($targetDateStr);
        } catch (\Throwable) {
            throw new ApiException(ApiError::InvalidTargetDateFormatCreate);
        }
        $g = (new Goal())
            ->setProfile($profile)
            ->setMeasurementType((string) ($data['measurementType'] ?? ''))
            ->setTargetValue(isset($data['targetValue']) ? (float) $data['targetValue'] : 0.0)
            ->setTargetDate($targetDate);
        $errors = $this->validator->validate($g);
        if (count($errors) > 0) {
            throw new ApiException(ApiError::ValidationFailed, null, [
                'messages' => array_map(fn ($e) => $e->getMessage(), iterator_to_array($errors)),
            ]);
        }
        $this->em->persist($g);
        $this->em->flush();
        return $this->goalToArray($g);
    }

    /** @return array<string, mixed> */
    public function get(string $id, string $userId): array
    {
        $g = $this->goalRepository->findOneById($id);
        if ($g === null || !$this->profileAccessChecker->canAccess($g->getProfile()->getId(), $userId)) {
            throw new ApiException(ApiError::GoalNotFound);
        }
        return $this->goalToArray($g);
    }

    /** @param array<string, mixed> $data */
    public function update(string $id, string $userId, array $data): array
    {
        $g = $this->goalRepository->findOneById($id);
        if ($g === null || !$this->profileAccessChecker->canAccess($g->getProfile()->getId(), $userId)) {
            throw new ApiException(ApiError::GoalNotFound);
        }
        if (isset($data['measurementType'])) {
            $g->setMeasurementType((string) $data['measurementType']);
        }
        if (array_key_exists('targetValue', $data)) {
            $g->setTargetValue((float) $data['targetValue']);
        }
        if (isset($data['targetDate'])) {
            try {
                $g->setTargetDate(new \DateTimeImmutable((string) $data['targetDate']));
            } catch (\Throwable) {
                throw new ApiException(ApiError::InvalidTargetDateFormat);
            }
        }
        $errors = $this->validator->validate($g);
        if (count($errors) > 0) {
            throw new ApiException(ApiError::ValidationFailed, null, [
                'messages' => array_map(fn ($e) => $e->getMessage(), iterator_to_array($errors)),
            ]);
        }
        $this->em->flush();
        return $this->goalToArray($g);
    }

    public function delete(string $id, string $userId): void
    {
        $g = $this->goalRepository->findOneById($id);
        if ($g === null || !$this->profileAccessChecker->canAccess($g->getProfile()->getId(), $userId)) {
            throw new ApiException(ApiError::GoalNotFound);
        }
        $this->em->remove($g);
        $this->em->flush();
    }

    /** @return array<string, mixed> */
    private function goalToArray(Goal $g): array
    {
        return [
            'id' => $g->getId(),
            'profileId' => $g->getProfile()->getId(),
            'measurementType' => $g->getMeasurementType(),
            'targetValue' => $g->getTargetValue(),
            'targetDate' => $g->getTargetDate()->format('Y-m-d'),
            'createdAt' => $g->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
