<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Goal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Goal>
 */
final class GoalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Goal::class);
    }

    /**
     * @return list<Goal>
     */
    public function findByProfileIdOrderByTargetDate(string $profileId): array
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.profile', 'p')
            ->andWhere('p.id = :profileId')
            ->setParameter('profileId', $profileId)
            ->orderBy('g.targetDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneById(string $id): ?Goal
    {
        $entity = $this->find(strtolower($id));
        return $entity ?? $this->find($id);
    }
}
