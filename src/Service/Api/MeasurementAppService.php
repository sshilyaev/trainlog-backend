<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Enum\ApiError;
use App\Api\ApiException;
use App\Entity\Measurement;
use App\Repository\MeasurementRepository;
use App\Repository\ProfileRepository;
use App\Service\ProfileAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class MeasurementAppService
{
    private const FLOAT_KEYS = ['weight', 'height', 'neck', 'shoulders', 'leftBiceps', 'rightBiceps', 'waist', 'belly', 'chest', 'leftThigh', 'rightThigh', 'hips', 'buttocks', 'leftCalf', 'rightCalf'];

    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly ProfileAccessChecker $profileAccessChecker,
        private readonly WeightSyncService $weightSyncService,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /** @return array{measurements: list<array<string, mixed>>} */
    public function list(string $profileId, string $userId): array
    {
        if ($profileId === '') {
            throw new ApiException(ApiError::ProfileIdQueryRequired);
        }
        if (!$this->profileAccessChecker->canAccess($profileId, $userId)) {
            throw new ApiException(ApiError::ProfileNotFound);
        }
        $measurements = $this->measurementRepository->findByProfileIdOrderByDateDesc($profileId);
        return [
            'measurements' => array_map([$this, 'measurementToArray'], $measurements),
        ];
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
        $dateStr = $data['date'] ?? '';
        try {
            $date = new \DateTimeImmutable($dateStr);
        } catch (\Throwable) {
            throw new ApiException(ApiError::InvalidDateFormat);
        }
        $m = (new Measurement())
            ->setProfile($profile)
            ->setDate($date);
        $this->applyMeasurementData($m, $data);
        $errors = $this->validator->validate($m);
        if (count($errors) > 0) {
            throw new ApiException(ApiError::ValidationFailed, null, [
                'messages' => array_map(fn ($e) => $e->getMessage(), iterator_to_array($errors)),
            ]);
        }
        $this->em->persist($m);
        if ($m->getWeight() !== null && $m->getWeight() > 0) {
            $this->weightSyncService->applyMeasurementToProfileIfFresh($profile, $m->getWeight(), $m->getDate());
        }
        $this->em->flush();
        return $this->measurementToArray($m);
    }

    /** @return array<string, mixed> */
    public function get(string $id, string $profileId, string $userId): array
    {
        $m = $this->measurementRepository->findOneById($id);
        if ($m === null || $m->getProfile()->getId() !== $profileId || !$this->profileAccessChecker->canAccess($profileId, $userId)) {
            throw new ApiException(ApiError::MeasurementNotFound);
        }
        return $this->measurementToArray($m);
    }

    /** @param array<string, mixed> $data */
    public function update(string $id, string $profileId, string $userId, array $data): array
    {
        $m = $this->measurementRepository->findOneById($id);
        if ($m === null || $m->getProfile()->getId() !== $profileId || !$this->profileAccessChecker->canAccess($profileId, $userId)) {
            throw new ApiException(ApiError::MeasurementNotFound);
        }
        if (isset($data['date'])) {
            try {
                $m->setDate(new \DateTimeImmutable((string) $data['date']));
            } catch (\Throwable) {
                throw new ApiException(ApiError::InvalidDateFormatShort);
            }
        }
        $this->applyMeasurementData($m, $data);
        if ($m->getWeight() !== null && $m->getWeight() > 0) {
            $this->weightSyncService->applyMeasurementToProfileIfFresh($m->getProfile(), $m->getWeight(), $m->getDate());
        }
        $errors = $this->validator->validate($m);
        if (count($errors) > 0) {
            throw new ApiException(ApiError::ValidationFailed, null, [
                'messages' => array_map(fn ($e) => $e->getMessage(), iterator_to_array($errors)),
            ]);
        }
        $this->em->flush();
        return $this->measurementToArray($m);
    }

    public function delete(string $id, string $profileId, string $userId): void
    {
        $m = $this->measurementRepository->findOneById($id);
        if ($m === null || $m->getProfile()->getId() !== $profileId || !$this->profileAccessChecker->canAccess($profileId, $userId)) {
            throw new ApiException(ApiError::MeasurementNotFound);
        }
        $this->em->remove($m);
        $this->em->flush();
    }

    /** @param array<string, mixed> $data */
    private function applyMeasurementData(Measurement $m, array $data): void
    {
        foreach (self::FLOAT_KEYS as $key) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                $m->{'set' . str_replace('_', '', ucwords($key, '_'))}($val === null ? null : (float) $val);
            }
        }
        if (array_key_exists('note', $data)) {
            $m->setNote($data['note'] === null ? null : (string) $data['note']);
        }
    }

    /** @return array<string, mixed> */
    private function measurementToArray(Measurement $m): array
    {
        return [
            'id' => $m->getId(),
            'profileId' => $m->getProfile()->getId(),
            'date' => $m->getDate()->format('Y-m-d'),
            'weight' => $m->getWeight(),
            'height' => $m->getHeight(),
            'neck' => $m->getNeck(),
            'shoulders' => $m->getShoulders(),
            'leftBiceps' => $m->getLeftBiceps(),
            'rightBiceps' => $m->getRightBiceps(),
            'waist' => $m->getWaist(),
            'belly' => $m->getBelly(),
            'chest' => $m->getChest(),
            'leftThigh' => $m->getLeftThigh(),
            'rightThigh' => $m->getRightThigh(),
            'hips' => $m->getHips(),
            'buttocks' => $m->getButtocks(),
            'leftCalf' => $m->getLeftCalf(),
            'rightCalf' => $m->getRightCalf(),
            'note' => $m->getNote(),
            'createdAt' => $m->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
