<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Api\ApiException;
use App\Entity\Profile;
use App\Entity\SupplementCatalog;
use App\Entity\TraineeSupplementAssignment;
use App\Enum\ApiError;
use App\Http\Request\Supplement\SupplementDosageUnitNormalizer;
use App\Http\Request\Supplement\UpdateSupplementAssignmentRequest;
use App\Repository\CoachTraineeLinkRepository;
use App\Repository\ProfileRepository;
use App\Repository\SupplementCatalogRepository;
use App\Repository\TraineeSupplementAssignmentRepository;
use Doctrine\ORM\EntityManagerInterface;

final class SupplementAppService
{
    public function __construct(
        private readonly SupplementCatalogRepository $supplementCatalogRepository,
        private readonly TraineeSupplementAssignmentRepository $assignmentRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly CoachTraineeLinkRepository $coachTraineeLinkRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return array{supplements: list<array<string, mixed>>}
     */
    public function listCatalog(?string $type): array
    {
        $items = $this->supplementCatalogRepository->findActiveOrdered($type);
        return ['supplements' => array_map([$this, 'catalogToArray'], $items)];
    }

    /**
     * @return array{assignments: list<array<string, mixed>>}
     */
    public function listAssignments(?string $coachProfileId, ?string $traineeProfileId, ?string $as, string $userId): array
    {
        if ($as === 'trainee') {
            if ($traineeProfileId === null || $traineeProfileId === '') {
                throw new ApiException(ApiError::TraineeProfileIdRequired);
            }
            $trainee = $this->profileRepository->find($traineeProfileId);
            if (!$trainee instanceof Profile || $trainee->getType() !== Profile::TYPE_TRAINEE || $trainee->getUserId() !== $userId) {
                throw new ApiException(ApiError::SupplementAssignmentNotFound);
            }
            $assignments = $this->assignmentRepository->findByTraineeProfileId($traineeProfileId);
            return ['assignments' => array_map([$this, 'assignmentToArray'], $assignments)];
        }

        if ($coachProfileId === null || $coachProfileId === '' || $traineeProfileId === null || $traineeProfileId === '') {
            throw new ApiException(ApiError::CoachAndTraineeProfileIdRequired);
        }

        $this->assertCoachOwnerAndLinked($coachProfileId, $traineeProfileId, $userId);
        $assignments = $this->assignmentRepository->findByCoachAndTrainee($coachProfileId, $traineeProfileId);
        return ['assignments' => array_map([$this, 'assignmentToArray'], $assignments)];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createAssignment(string $userId, array $data): array
    {
        $coachProfileId = (string) ($data['coachProfileId'] ?? '');
        $traineeProfileId = (string) ($data['traineeProfileId'] ?? '');
        $supplementId = (string) ($data['supplementId'] ?? '');

        $coachProfile = $this->assertCoachOwnerAndLinked($coachProfileId, $traineeProfileId, $userId);
        $traineeProfile = $this->assertTrainee($traineeProfileId);
        $supplement = $this->assertActiveSupplement($supplementId);

        if ($this->assignmentRepository->existsDuplicate($coachProfileId, $traineeProfileId, $supplementId)) {
            throw new ApiException(ApiError::SupplementAssignmentDuplicate);
        }

        $assignment = (new TraineeSupplementAssignment())
            ->setCoachProfile($coachProfile)
            ->setTraineeProfile($traineeProfile)
            ->setSupplement($supplement)
            ->setDosage($this->normalizeNullableString($data['dosage'] ?? null))
            ->setDosageValue($this->normalizeNullableString($data['dosageValue'] ?? null))
            ->setDosageUnit($this->resolveDosageUnit(
                $data['dosageUnit'] ?? null,
                $supplement->getDefaultDosageUnit()
            ))
            ->setTiming($this->normalizeNullableString($data['timing'] ?? null))
            ->setFrequency($this->normalizeNullableString($data['frequency'] ?? null))
            ->setNote($this->normalizeNullableString($data['note'] ?? null));

        $this->em->persist($assignment);
        $this->em->flush();

        return $this->assignmentToArray($assignment);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function updateAssignment(string $id, string $userId, array $data): array
    {
        $assignment = $this->assignmentRepository->findOneById($id);
        if ($assignment === null) {
            throw new ApiException(ApiError::SupplementAssignmentNotFound);
        }

        if ($assignment->getCoachProfile()->getUserId() !== $userId) {
            throw new ApiException(ApiError::SupplementAssignmentForbidden);
        }

        $coachProfileId = $assignment->getCoachProfile()->getId();
        $traineeProfileId = $assignment->getTraineeProfile()->getId();

        $supplementChanged = false;
        if (array_key_exists('supplementId', $data)) {
            $supplementId = (string) $data['supplementId'];
            $supplement = $this->assertActiveSupplement($supplementId);
            $assignment->setSupplement($supplement);
            $supplementChanged = true;
        }

        if (array_key_exists('dosage', $data)) {
            $assignment->setDosage($this->normalizeNullableString($data['dosage']));
        }
        if (array_key_exists('dosageValue', $data)) {
            $assignment->setDosageValue($this->normalizeNullableString($data['dosageValue']));
        }
        if (array_key_exists('dosageUnit', $data)) {
            $assignment->setDosageUnit($this->normalizeDosageUnit($data['dosageUnit']));
        } elseif ($supplementChanged) {
            // If supplement was switched and dosageUnit not sent explicitly,
            // reset to catalog default for better UX consistency.
            $assignment->setDosageUnit($this->normalizeDosageUnit($assignment->getSupplement()->getDefaultDosageUnit()));
        }
        if (array_key_exists('timing', $data)) {
            $assignment->setTiming($this->normalizeNullableString($data['timing']));
        }
        if (array_key_exists('frequency', $data)) {
            $assignment->setFrequency($this->normalizeNullableString($data['frequency']));
        }
        if (array_key_exists('note', $data)) {
            $assignment->setNote($this->normalizeNullableString($data['note']));
        }

        if ($this->assignmentRepository->existsDuplicate(
            $coachProfileId,
            $traineeProfileId,
            $assignment->getSupplement()->getId(),
            $assignment->getId()
        )) {
            throw new ApiException(ApiError::SupplementAssignmentDuplicate);
        }

        $assignment->touch();
        $this->em->flush();

        return $this->assignmentToArray($assignment);
    }

    public function updateAssignmentFromPayload(string $id, string $userId, UpdateSupplementAssignmentRequest $payload, string $rawJson): array
    {
        $raw = json_decode($rawJson, true);
        $raw = is_array($raw) ? $raw : [];

        // Preserve "key exists with null" semantics for PATCH/PUT.
        $data = [];
        if (array_key_exists('supplementId', $raw)) {
            $data['supplementId'] = $payload->supplementId;
        }
        if (array_key_exists('dosage', $raw)) {
            $data['dosage'] = $payload->dosage;
        }
        if (array_key_exists('dosageValue', $raw)) {
            $data['dosageValue'] = $payload->dosageValue;
        }
        if (array_key_exists('dosageUnit', $raw)) {
            $data['dosageUnit'] = $payload->dosageUnit;
        }
        if (array_key_exists('timing', $raw)) {
            $data['timing'] = $payload->timing;
        }
        if (array_key_exists('frequency', $raw)) {
            $data['frequency'] = $payload->frequency;
        }
        if (array_key_exists('note', $raw)) {
            $data['note'] = $payload->note;
        }

        return $this->updateAssignment($id, $userId, $data);
    }

    public function deleteAssignment(string $id, string $userId): void
    {
        $assignment = $this->assignmentRepository->findOneById($id);
        if ($assignment === null) {
            throw new ApiException(ApiError::SupplementAssignmentNotFound);
        }
        if ($assignment->getCoachProfile()->getUserId() !== $userId) {
            throw new ApiException(ApiError::SupplementAssignmentForbidden);
        }

        $this->em->remove($assignment);
        $this->em->flush();
    }

    private function assertCoachOwnerAndLinked(string $coachProfileId, string $traineeProfileId, string $userId): Profile
    {
        $coach = $this->profileRepository->find($coachProfileId);
        if (!$coach instanceof Profile || $coach->getType() !== Profile::TYPE_COACH || $coach->getUserId() !== $userId) {
            throw new ApiException(ApiError::SupplementAssignmentForbidden);
        }
        if (!$this->coachTraineeLinkRepository->existsLink($coachProfileId, $traineeProfileId)) {
            throw new ApiException(ApiError::SupplementAssignmentForbidden);
        }

        return $coach;
    }

    private function assertTrainee(string $traineeProfileId): Profile
    {
        $trainee = $this->profileRepository->find($traineeProfileId);
        if (!$trainee instanceof Profile || $trainee->getType() !== Profile::TYPE_TRAINEE) {
            throw new ApiException(ApiError::TraineeProfileNotFound);
        }

        return $trainee;
    }

    private function assertActiveSupplement(string $id): SupplementCatalog
    {
        $supplement = $this->supplementCatalogRepository->findActiveById($id);
        if ($supplement === null) {
            throw new ApiException(ApiError::SupplementNotFound);
        }

        return $supplement;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }

    private function normalizeDosageUnit(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return SupplementDosageUnitNormalizer::normalizeOptional((string) $value);
    }

    private function resolveDosageUnit(mixed $requested, ?string $fallback): ?string
    {
        return $this->normalizeDosageUnit($requested) ?? $this->normalizeDosageUnit($fallback);
    }

    /** @return array<string, mixed> */
    private function catalogToArray(SupplementCatalog $item): array
    {
        return [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'type' => $item->getType(),
            'description' => $item->getDescription(),
            'isActive' => $item->isActive(),
            'sortOrder' => $item->getSortOrder(),
            'defaultDosageUnit' => $item->getDefaultDosageUnit(),
            'createdAt' => $item->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $item->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    private function assignmentToArray(TraineeSupplementAssignment $item): array
    {
        $supplement = $item->getSupplement();

        return [
            'id' => $item->getId(),
            'coachProfileId' => $item->getCoachProfile()->getId(),
            'traineeProfileId' => $item->getTraineeProfile()->getId(),
            'supplementId' => $supplement->getId(),
            'supplement' => [
                'id' => $supplement->getId(),
                'name' => $supplement->getName(),
                'type' => $supplement->getType(),
                'description' => $supplement->getDescription(),
            ],
            'dosage' => $item->getDosage() ?? $this->composeLegacyDosage($item->getDosageValue(), $item->getDosageUnit()),
            'dosageValue' => $item->getDosageValue(),
            'dosageUnit' => $item->getDosageUnit(),
            'timing' => $item->getTiming(),
            'frequency' => $item->getFrequency(),
            'note' => $item->getNote(),
            'createdAt' => $item->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $item->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function composeLegacyDosage(?string $value, ?string $unit): ?string
    {
        $v = $this->normalizeNullableString($value);
        if ($v === null) {
            return null;
        }
        $u = $this->normalizeNullableString($unit);
        if ($u === null) {
            return $v;
        }
        return sprintf('%s %s', $v, $u);
    }
}

