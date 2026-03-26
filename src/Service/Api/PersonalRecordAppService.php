<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Api\ApiException;
use App\Entity\PersonalRecord;
use App\Entity\PersonalRecordMetric;
use App\Enum\ApiError;
use App\Repository\PersonalRecordRepository;
use App\Repository\ProfileRepository;
use App\Repository\RecordActivityCatalogRepository;
use App\Service\ProfileAccessChecker;
use Doctrine\ORM\EntityManagerInterface;

final class PersonalRecordAppService
{
    private const METRIC_UNITS = [
        'weight' => 'kg',
        'reps' => 'reps',
        'duration' => 'sec',
        'speed' => 'kmh',
        'distance' => 'm',
        'other' => null,
    ];

    public function __construct(
        private readonly PersonalRecordRepository $personalRecordRepository,
        private readonly RecordActivityCatalogRepository $recordActivityCatalogRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly ProfileAccessChecker $profileAccessChecker,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @return array{records: list<array<string, mixed>>} */
    public function list(string $profileId, string $userId): array
    {
        if ($profileId === '' || !$this->profileAccessChecker->canAccess($profileId, $userId)) {
            throw new ApiException(ApiError::ProfileNotFound);
        }
        $records = $this->personalRecordRepository->findByProfileIdOrderByDateDesc($profileId);
        return ['records' => array_map([$this, 'recordToArray'], $records)];
    }

    /** @return array{activities: list<array<string, mixed>>} */
    public function listActivities(?string $q, ?string $activityType, int $limit, int $offset): array
    {
        $list = $this->recordActivityCatalogRepository->searchActive($q, $activityType, $limit, $offset);
        return [
            'activities' => array_map(
                static fn ($a) => [
                    'slug' => $a->getSlug(),
                    'name' => $a->getName(),
                    'activityType' => $a->getActivityType(),
                    'defaultMetrics' => $a->getDefaultMetrics(),
                    'displayOrder' => $a->getDisplayOrder(),
                ],
                $list
            ),
        ];
    }

    /** @param array<string, mixed> $data */
    public function create(string $profileId, string $userId, array $data): array
    {
        $profile = $this->profileRepository->find($profileId);
        if ($profile === null || !$this->profileAccessChecker->canAccess($profileId, $userId)) {
            throw new ApiException(ApiError::ProfileNotFound);
        }
        if ($profile->getUserId() !== $userId) {
            throw new ApiException(ApiError::OnlyOwnerCanManageRecords);
        }

        $record = new PersonalRecord();
        $record->setProfile($profile)
            ->setCreatedByProfile($profile);
        $this->applyData($record, $data, true);

        $this->em->persist($record);
        $this->em->flush();

        return $this->recordToArray($record);
    }

    /** @return array<string, mixed> */
    public function get(string $id, string $profileId, string $userId): array
    {
        $record = $this->personalRecordRepository->findOneByIdWithMetrics($id);
        if ($record === null || $record->getProfile()->getId() !== $profileId || !$this->profileAccessChecker->canAccess($profileId, $userId)) {
            throw new ApiException(ApiError::PersonalRecordNotFound);
        }
        return $this->recordToArray($record);
    }

    /** @param array<string, mixed> $data */
    public function update(string $id, string $profileId, string $userId, array $data): array
    {
        $record = $this->personalRecordRepository->findOneByIdWithMetrics($id);
        if ($record === null || $record->getProfile()->getId() !== $profileId || !$this->profileAccessChecker->canAccess($profileId, $userId)) {
            throw new ApiException(ApiError::PersonalRecordNotFound);
        }
        if ($record->getProfile()->getUserId() !== $userId) {
            throw new ApiException(ApiError::OnlyOwnerCanManageRecords);
        }
        $this->applyData($record, $data, false);
        $record->touch();
        $this->em->flush();

        return $this->recordToArray($record);
    }

    public function delete(string $id, string $profileId, string $userId): void
    {
        $record = $this->personalRecordRepository->findOneByIdWithMetrics($id);
        if ($record === null || $record->getProfile()->getId() !== $profileId || !$this->profileAccessChecker->canAccess($profileId, $userId)) {
            throw new ApiException(ApiError::PersonalRecordNotFound);
        }
        if ($record->getProfile()->getUserId() !== $userId) {
            throw new ApiException(ApiError::OnlyOwnerCanManageRecords);
        }
        $this->em->remove($record);
        $this->em->flush();
    }

    /** @param array<string, mixed> $data */
    private function applyData(PersonalRecord $record, array $data, bool $isCreate): void
    {
        if ($isCreate || array_key_exists('recordDate', $data)) {
            $recordDateRaw = (string) ($data['recordDate'] ?? '');
            try {
                $recordDate = new \DateTimeImmutable($recordDateRaw);
            } catch (\Throwable) {
                throw new ApiException(ApiError::InvalidDateFormat);
            }
            if ($recordDate > new \DateTimeImmutable('today')) {
                throw new ApiException(ApiError::ValidationFailed, null, ['messages' => ['Дата рекорда не может быть в будущем']]);
            }
            $record->setRecordDate($recordDate);
        }

        $sourceType = (string) ($data['sourceType'] ?? ($isCreate ? '' : $record->getSourceType()));
        if (!in_array($sourceType, ['catalog', 'custom'], true)) {
            throw new ApiException(ApiError::ValidationFailed, null, ['messages' => ['sourceType должен быть catalog или custom']]);
        }
        $record->setSourceType($sourceType);

        $activityType = array_key_exists('activityType', $data) ? $this->cleanOptionalString($data['activityType']) : $record->getActivityType();
        $record->setActivityType($activityType);

        if ($sourceType === 'catalog') {
            $slug = trim((string) ($data['activitySlug'] ?? ''));
            if ($slug === '') {
                throw new ApiException(ApiError::ValidationFailed, null, ['messages' => ['Для sourceType=catalog укажите activitySlug']]);
            }
            $activity = $this->recordActivityCatalogRepository->findActiveBySlug($slug);
            if ($activity === null) {
                throw new ApiException(ApiError::RecordActivityNotFound);
            }
            $record->setActivityName($activity->getName());
            if ($record->getActivityType() === null || $record->getActivityType() === '') {
                $record->setActivityType($activity->getActivityType());
            }
        } else {
            $activityName = array_key_exists('activityName', $data)
                ? $this->cleanRequiredString($data['activityName'], 'Для sourceType=custom укажите activityName')
                : $record->getActivityName();
            $record->setActivityName($activityName);
        }

        if (array_key_exists('notes', $data) || $isCreate) {
            $record->setNotes($this->cleanOptionalString($data['notes'] ?? null));
        }

        if ($isCreate || array_key_exists('metrics', $data)) {
            $metrics = $data['metrics'] ?? [];
            if (!is_array($metrics) || count($metrics) === 0) {
                throw new ApiException(ApiError::ValidationFailed, null, ['messages' => ['Добавьте минимум один показатель рекорда']]);
            }
            $record->clearMetrics();
            $order = 0;
            foreach ($metrics as $metricInput) {
                if (!is_array($metricInput)) {
                    throw new ApiException(ApiError::ValidationFailed, null, ['messages' => ['Неверный формат metrics']]);
                }
                $metricType = trim((string) ($metricInput['metricType'] ?? ''));
                if (!array_key_exists($metricType, self::METRIC_UNITS)) {
                    throw new ApiException(ApiError::ValidationFailed, null, ['messages' => ["Неподдерживаемый metricType: {$metricType}"]]);
                }
                $value = (float) ($metricInput['value'] ?? 0);
                if ($value <= 0) {
                    throw new ApiException(ApiError::ValidationFailed, null, ['messages' => ['value должен быть больше 0']]);
                }
                $unit = trim((string) ($metricInput['unit'] ?? ''));
                $expectedUnit = self::METRIC_UNITS[$metricType];
                if ($expectedUnit === null) {
                    if ($unit === '') {
                        throw new ApiException(ApiError::ValidationFailed, null, ['messages' => ['Для metricType=other укажите unit']]);
                    }
                } else {
                    $unit = $expectedUnit;
                }
                $metric = (new PersonalRecordMetric())
                    ->setMetricType($metricType)
                    ->setValue($value)
                    ->setUnit($unit)
                    ->setDisplayOrder($order++);
                $record->addMetric($metric);
            }
        }
    }

    private function cleanOptionalString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $cleaned = trim((string) $value);
        return $cleaned === '' ? null : $cleaned;
    }

    private function cleanRequiredString(mixed $value, string $message): string
    {
        $cleaned = trim((string) $value);
        if ($cleaned === '') {
            throw new ApiException(ApiError::ValidationFailed, null, ['messages' => [$message]]);
        }
        return $cleaned;
    }

    /** @return array<string, mixed> */
    private function recordToArray(PersonalRecord $record): array
    {
        $metrics = [];
        foreach ($record->getMetrics() as $metric) {
            $metrics[] = [
                'id' => $metric->getId(),
                'metricType' => $metric->getMetricType(),
                'value' => $metric->getValue(),
                'unit' => $metric->getUnit(),
                'displayOrder' => $metric->getDisplayOrder(),
            ];
        }

        return [
            'id' => $record->getId(),
            'profileId' => $record->getProfile()->getId(),
            'createdByProfileId' => $record->getCreatedByProfile()->getId(),
            'recordDate' => $record->getRecordDate()->format('Y-m-d'),
            'sourceType' => $record->getSourceType(),
            'activityName' => $record->getActivityName(),
            'activityType' => $record->getActivityType(),
            'notes' => $record->getNotes(),
            'metrics' => $metrics,
            'createdAt' => $record->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $record->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
