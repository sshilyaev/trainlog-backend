<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Profile>
 */
final class ProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Profile::class);
    }

    /**
     * @return list<Profile>
     */
    public function findByUserId(string $userId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?object
    {
        if (!\is_string($id)) {
            return parent::find($id, $lockMode, $lockVersion);
        }
        $entity = parent::find(strtolower($id), $lockMode, $lockVersion);
        if ($entity !== null) {
            return $entity;
        }
        return $id !== strtolower($id) ? parent::find($id, $lockMode, $lockVersion) : null;
    }

    /**
     * Find profiles by list of IDs (order not guaranteed).
     *
     * @param list<string> $ids
     * @return list<Profile>
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $ids = array_unique(array_map('strtolower', $ids));
        return $this->createQueryBuilder('p')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdAndUserId(string $id, string $userId): ?Profile
    {
        $entity = $this->findOneByIdAndUserIdNormalized(strtolower($id), $userId);
        if ($entity !== null) {
            return $entity;
        }
        return $id !== strtolower($id) ? $this->findOneByIdAndUserIdNormalized($id, $userId) : null;
    }

    /**
     * @return list<string>
     */
    public function findCoachProfileIdsByUserId(string $userId): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.id')
            ->andWhere('p.userId = :userId')
            ->andWhere('p.type = :type')
            ->setParameter('userId', $userId)
            ->setParameter('type', Profile::TYPE_COACH)
            ->getQuery()
            ->getArrayResult();

        return array_values(array_map(static fn (array $row): string => (string) $row['id'], $rows));
    }

    private function findOneByIdAndUserIdNormalized(string $id, string $userId): ?Profile
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id = :id')
            ->andWhere('p.userId = :userId')
            ->setParameter('id', $id)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
