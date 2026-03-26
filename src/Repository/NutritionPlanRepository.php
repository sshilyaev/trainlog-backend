<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\NutritionPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NutritionPlan>
 */
final class NutritionPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NutritionPlan::class);
    }

    /** @return list<NutritionPlan> */
    public function findByCoachAndTrainee(string $coachProfileId, string $traineeProfileId): array
    {
        return $this->createQueryBuilder('np')
            ->innerJoin('np.coachProfile', 'c')
            ->innerJoin('np.traineeProfile', 't')
            ->andWhere('c.id = :coachProfileId')
            ->andWhere('t.id = :traineeProfileId')
            ->setParameter('coachProfileId', $coachProfileId)
            ->setParameter('traineeProfileId', $traineeProfileId)
            ->orderBy('np.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByCoachAndTrainee(string $coachProfileId, string $traineeProfileId): ?NutritionPlan
    {
        return $this->createQueryBuilder('np')
            ->innerJoin('np.coachProfile', 'c')
            ->innerJoin('np.traineeProfile', 't')
            ->andWhere('c.id = :coachProfileId')
            ->andWhere('t.id = :traineeProfileId')
            ->setParameter('coachProfileId', $coachProfileId)
            ->setParameter('traineeProfileId', $traineeProfileId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<NutritionPlan> */
    public function findByTraineeProfileId(string $traineeProfileId): array
    {
        return $this->createQueryBuilder('np')
            ->innerJoin('np.traineeProfile', 't')
            ->andWhere('t.id = :traineeProfileId')
            ->setParameter('traineeProfileId', $traineeProfileId)
            ->orderBy('np.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneById(string $id): ?NutritionPlan
    {
        $entity = $this->find(strtolower($id));
        return $entity ?? $this->find($id);
    }
}

