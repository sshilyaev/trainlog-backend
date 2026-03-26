<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SupplementCatalog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupplementCatalog>
 */
final class SupplementCatalogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplementCatalog::class);
    }

    /**
     * @return list<SupplementCatalog>
     */
    public function findActiveOrdered(?string $type = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.isActive = true')
            ->orderBy('s.sortOrder', 'ASC')
            ->addOrderBy('s.name', 'ASC');

        if ($type !== null && $type !== '') {
            $qb->andWhere('s.type = :type')->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    public function findActiveById(string $id): ?SupplementCatalog
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.id = :id')
            ->andWhere('s.isActive = true')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

