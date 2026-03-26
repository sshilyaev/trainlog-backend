<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TraineeSupplementAssignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TraineeSupplementAssignment>
 */
final class TraineeSupplementAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TraineeSupplementAssignment::class);
    }

    /**
     * @return list<TraineeSupplementAssignment>
     */
    public function findByCoachAndTrainee(string $coachProfileId, string $traineeProfileId): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.supplement', 's')->addSelect('s')
            ->andWhere('a.coachProfile = :coachProfileId')
            ->andWhere('a.traineeProfile = :traineeProfileId')
            ->setParameter('coachProfileId', $coachProfileId)
            ->setParameter('traineeProfileId', $traineeProfileId)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<TraineeSupplementAssignment>
     */
    public function findByTraineeProfileId(string $traineeProfileId): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.supplement', 's')->addSelect('s')
            ->andWhere('a.traineeProfile = :traineeProfileId')
            ->setParameter('traineeProfileId', $traineeProfileId)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneById(string $id): ?TraineeSupplementAssignment
    {
        return $this->createQueryBuilder('a')
            ->join('a.supplement', 's')->addSelect('s')
            ->andWhere('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsDuplicate(string $coachProfileId, string $traineeProfileId, string $supplementId, ?string $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('a')
            ->select('1')
            ->andWhere('a.coachProfile = :coachProfileId')
            ->andWhere('a.traineeProfile = :traineeProfileId')
            ->andWhere('a.supplement = :supplementId')
            ->setParameter('coachProfileId', $coachProfileId)
            ->setParameter('traineeProfileId', $traineeProfileId)
            ->setParameter('supplementId', $supplementId)
            ->setMaxResults(1);

        if ($excludeId !== null && $excludeId !== '') {
            $qb->andWhere('a.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getOneOrNullResult() !== null;
    }
}

