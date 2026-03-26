<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RecordActivityCatalog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RecordActivityCatalog>
 */
final class RecordActivityCatalogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecordActivityCatalog::class);
    }

    /**
     * @return list<RecordActivityCatalog>
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.isActive = true')
            ->orderBy('a.displayOrder', 'ASC')
            ->addOrderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<RecordActivityCatalog>
     */
    public function searchActive(?string $q, ?string $activityType, int $limit, int $offset): array
    {
        $limit = max(1, min($limit, 500));
        $offset = max(0, $offset);

        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.isActive = true');

        $q = $q !== null ? trim($q) : '';
        if ($q !== '') {
            $qb
                ->andWhere('(LOWER(a.name) LIKE :q OR LOWER(a.slug) LIKE :q)')
                ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $activityType = $activityType !== null ? trim($activityType) : '';
        if ($activityType !== '') {
            $qb
                ->andWhere('a.activityType = :activityType')
                ->setParameter('activityType', $activityType);
        }

        return $qb
            ->orderBy('a.displayOrder', 'ASC')
            ->addOrderBy('a.name', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findActiveBySlug(string $slug): ?RecordActivityCatalog
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.slug = :slug')
            ->andWhere('a.isActive = true')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
