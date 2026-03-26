<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Entity\Measurement;
use App\Entity\Profile;
use App\Repository\MeasurementRepository;
use Doctrine\ORM\EntityManagerInterface;

final class WeightSyncService
{
    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Sets current profile weight and upserts measurement for the date.
     */
    public function setCurrentWeight(Profile $profile, float $weight, \DateTimeImmutable $date, bool $createOrUpdateMeasurement = true): void
    {
        $normalizedDate = $date->setTime(0, 0);
        $profile->setWeight($weight);
        $profile->setWeightUpdatedAt($normalizedDate);

        if (!$createOrUpdateMeasurement) {
            return;
        }

        $measurement = $this->measurementRepository->findOneByProfileIdAndDate($profile->getId(), $normalizedDate);
        if ($measurement === null) {
            $measurement = (new Measurement())
                ->setProfile($profile)
                ->setDate($normalizedDate);
            $this->em->persist($measurement);
        }
        $measurement->setWeight($weight);
    }

    /**
     * Applies measurement weight to profile only if this measurement date is newer than current profile weight date.
     */
    public function applyMeasurementToProfileIfFresh(Profile $profile, float $weight, \DateTimeImmutable $measurementDate): void
    {
        $measurementDay = $measurementDate->setTime(0, 0);
        $currentWeightDay = $profile->getWeightUpdatedAt()?->setTime(0, 0);

        if ($currentWeightDay === null || $measurementDay > $currentWeightDay) {
            $profile->setWeight($weight);
            $profile->setWeightUpdatedAt($measurementDay);
        }
    }
}

