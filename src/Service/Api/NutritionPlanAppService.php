<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Api\ApiException;
use App\Entity\NutritionPlan;
use App\Entity\Profile;
use App\Enum\ApiError;
use App\Repository\CoachTraineeLinkRepository;
use App\Repository\MeasurementRepository;
use App\Repository\NutritionPlanRepository;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;

final class NutritionPlanAppService
{
    public function __construct(
        private readonly NutritionPlanRepository $nutritionPlanRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly CoachTraineeLinkRepository $coachTraineeLinkRepository,
        private readonly MeasurementRepository $measurementRepository,
        private readonly WeightSyncService $weightSyncService,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return array{nutritionPlans: list<array<string, mixed>>, coachProfiles?: list<array<string, mixed>>}
     */
    public function list(
        ?string $coachProfileId,
        ?string $traineeProfileId,
        ?string $as,
        ?string $embed,
        string $userId
    ): array {
        if ($as === 'trainee') {
            if ($traineeProfileId === null || $traineeProfileId === '') {
                throw new ApiException(ApiError::TraineeProfileIdRequired);
            }
            $trainee = $this->profileRepository->find($traineeProfileId);
            if (!$trainee instanceof Profile || $trainee->getType() !== Profile::TYPE_TRAINEE || $trainee->getUserId() !== $userId) {
                throw new ApiException(ApiError::ProfileNotFound);
            }
            $plans = $this->nutritionPlanRepository->findByTraineeProfileId($traineeProfileId);
            $result = ['nutritionPlans' => array_map([$this, 'toArray'], $plans)];
            if ($embed === 'coachProfiles') {
                $coaches = [];
                foreach ($plans as $plan) {
                    $coach = $plan->getCoachProfile();
                    $coaches[$coach->getId()] = [
                        'id' => $coach->getId(),
                        'userId' => $coach->getUserId(),
                        'type' => $coach->getType(),
                        'name' => $coach->getName(),
                        'gymName' => $coach->getGymName(),
                        'createdAt' => $coach->getCreatedAt()->format(\DateTimeInterface::ATOM),
                    ];
                }
                $result['coachProfiles'] = array_values($coaches);
            }
            return $result;
        }

        if ($coachProfileId === null || $coachProfileId === '' || $traineeProfileId === null || $traineeProfileId === '') {
            throw new ApiException(ApiError::CoachAndTraineeProfileIdRequired);
        }
        $coach = $this->profileRepository->find($coachProfileId);
        if (!$coach instanceof Profile || $coach->getType() !== Profile::TYPE_COACH || $coach->getUserId() !== $userId) {
            throw new ApiException(ApiError::CoachProfileNotFound);
        }
        if (!$this->coachTraineeLinkRepository->existsLink($coachProfileId, $traineeProfileId)) {
            throw new ApiException(ApiError::CoachAndTraineeMustBeLinked);
        }
        $plans = $this->nutritionPlanRepository->findByCoachAndTrainee($coachProfileId, $traineeProfileId);
        return ['nutritionPlans' => array_map([$this, 'toArray'], $plans)];
    }

    /** @param array<string, mixed> $data */
    public function create(string $userId, array $data): array
    {
        $coachProfileId = (string) ($data['coachProfileId'] ?? '');
        $traineeProfileId = (string) ($data['traineeProfileId'] ?? '');

        $coach = $this->assertCoachOwner($coachProfileId, $userId);
        $trainee = $this->assertTrainee($traineeProfileId);
        $this->assertLinked($coachProfileId, $traineeProfileId);

        if ($this->nutritionPlanRepository->findOneByCoachAndTrainee($coachProfileId, $traineeProfileId) !== null) {
            throw new ApiException(ApiError::NutritionPlanAlreadyExists);
        }

        $weightKgUsed = $this->resolveWeight($trainee, isset($data['weightKg']) ? (float) $data['weightKg'] : null);
        $plan = (new NutritionPlan())
            ->setCoachProfile($coach)
            ->setTraineeProfile($trainee)
            ->setProteinPerKg((float) $data['proteinPerKg'])
            ->setFatPerKg((float) $data['fatPerKg'])
            ->setCarbsPerKg((float) $data['carbsPerKg'])
            ->setComment(isset($data['comment']) ? ($data['comment'] !== null ? (string) $data['comment'] : null) : null);

        $this->applyCalculatedFields($plan, $weightKgUsed);
        $this->em->persist($plan);
        $this->em->flush();
        return $this->toArray($plan);
    }

    /** @param array<string, mixed> $data */
    public function update(string $id, string $userId, array $data): array
    {
        $plan = $this->nutritionPlanRepository->findOneById($id);
        if ($plan === null) {
            throw new ApiException(ApiError::NutritionPlanNotFound);
        }

        $isCoachOwner = $plan->getCoachProfile()->getUserId() === $userId;
        $isTraineeOwner = $plan->getTraineeProfile()->getUserId() === $userId;

        // Coach can edit everything (coach-flow).
        if ($isCoachOwner) {
            if (array_key_exists('proteinPerKg', $data)) {
                $plan->setProteinPerKg((float) $data['proteinPerKg']);
            }
            if (array_key_exists('fatPerKg', $data)) {
                $plan->setFatPerKg((float) $data['fatPerKg']);
            }
            if (array_key_exists('carbsPerKg', $data)) {
                $plan->setCarbsPerKg((float) $data['carbsPerKg']);
            }
            if (array_key_exists('comment', $data)) {
                $plan->setComment((string) $data['comment']);
            }

            $payloadWeight = array_key_exists('weightKg', $data) ? (float) $data['weightKg'] : null;
            $weightKgUsed = $this->resolveWeight($plan->getTraineeProfile(), $payloadWeight);
            $this->applyCalculatedFields($plan, $weightKgUsed);
            $plan->touch();
            $this->em->flush();
            return $this->toArray($plan);
        }

        // Trainee (Дневник) can edit only weightKg.
        if ($isTraineeOwner) {
            $allowedKeys = ['weightKg'];
            $disallowedKeys = array_values(array_diff(array_keys($data), $allowedKeys));
            if ($disallowedKeys !== []) {
                throw new ApiException(ApiError::TraineeCanUpdateOnlyWeightKg);
            }
            if (!array_key_exists('weightKg', $data)) {
                throw new ApiException(ApiError::WeightRequired);
            }

            $payloadWeight = (float) $data['weightKg'];
            $weightKgUsed = $this->resolveWeight($plan->getTraineeProfile(), $payloadWeight);
            $this->applyCalculatedFields($plan, $weightKgUsed);
            $plan->touch();
            $this->em->flush();
            return $this->toArray($plan);
        }

        throw new ApiException(ApiError::OnlyCoachCanUpdateNutritionPlan);
    }

    private function assertCoachOwner(string $coachProfileId, string $userId): Profile
    {
        $coach = $this->profileRepository->find($coachProfileId);
        if (!$coach instanceof Profile || $coach->getType() !== Profile::TYPE_COACH || $coach->getUserId() !== $userId) {
            throw new ApiException(ApiError::CoachProfileNotFound);
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

    private function assertLinked(string $coachProfileId, string $traineeProfileId): void
    {
        if (!$this->coachTraineeLinkRepository->existsLink($coachProfileId, $traineeProfileId)) {
            throw new ApiException(ApiError::CoachAndTraineeMustBeLinked);
        }
    }

    private function resolveWeight(Profile $traineeProfile, ?float $payloadWeight): float
    {
        if ($payloadWeight !== null) {
            if ($payloadWeight <= 0) {
                throw new ApiException(ApiError::WeightRequired);
            }
            $this->weightSyncService->setCurrentWeight($traineeProfile, $payloadWeight, new \DateTimeImmutable('today'));
            return $payloadWeight;
        }

        if ($traineeProfile->getWeight() !== null && $traineeProfile->getWeight() > 0) {
            return $traineeProfile->getWeight();
        }

        $latest = $this->measurementRepository->findLatestByProfileId($traineeProfile->getId());
        $latestWeight = $latest?->getWeight();
        if ($latestWeight === null || $latestWeight <= 0) {
            throw new ApiException(ApiError::WeightRequired);
        }
        $this->weightSyncService->applyMeasurementToProfileIfFresh($traineeProfile, $latestWeight, $latest->getDate());
        return $latestWeight;
    }

    private function applyCalculatedFields(NutritionPlan $plan, float $weightKgUsed): void
    {
        $proteinGrams = $plan->getProteinPerKg() * $weightKgUsed;
        $fatGrams = $plan->getFatPerKg() * $weightKgUsed;
        $carbsGrams = $plan->getCarbsPerKg() * $weightKgUsed;
        $calories = (int) round($proteinGrams * 4 + $fatGrams * 9 + $carbsGrams * 4);

        $plan
            ->setWeightKgUsed($weightKgUsed)
            ->setProteinGrams($proteinGrams)
            ->setFatGrams($fatGrams)
            ->setCarbsGrams($carbsGrams)
            ->setCalories($calories);
    }

    /** @return array<string, mixed> */
    private function toArray(NutritionPlan $plan): array
    {
        return [
            'id' => $plan->getId(),
            'coachProfileId' => $plan->getCoachProfile()->getId(),
            'traineeProfileId' => $plan->getTraineeProfile()->getId(),
            'weightKgUsed' => $plan->getWeightKgUsed(),
            'proteinPerKg' => $plan->getProteinPerKg(),
            'fatPerKg' => $plan->getFatPerKg(),
            'carbsPerKg' => $plan->getCarbsPerKg(),
            'proteinGrams' => $plan->getProteinGrams(),
            'fatGrams' => $plan->getFatGrams(),
            'carbsGrams' => $plan->getCarbsGrams(),
            'calories' => $plan->getCalories(),
            'comment' => $plan->getComment(),
            'createdAt' => $plan->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $plan->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}

