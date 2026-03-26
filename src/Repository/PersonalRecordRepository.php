<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PersonalRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PersonalRecord>
 */
final class PersonalRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalRecord::class);
    }

    /**
     * @return list<PersonalRecord>
     */
    public function findByProfileIdOrderByDateDesc(string $profileId): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.profile', 'p')
            ->leftJoin('r.metrics', 'm')
            ->addSelect('m')
            ->andWhere('p.id = :profileId')
            ->setParameter('profileId', $profileId)
            ->orderBy('r.recordDate', 'DESC')
            ->addOrderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdWithMetrics(string $id): ?PersonalRecord
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.metrics', 'm')
            ->addSelect('m')
            ->andWhere('r.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
