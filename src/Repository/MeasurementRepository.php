<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Measurement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Measurement>
 */
final class MeasurementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Measurement::class);
    }

    /**
     * @return list<Measurement>
     */
    public function findByProfileIdOrderByDateDesc(string $profileId): array
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.profile', 'p')
            ->andWhere('p.id = :profileId')
            ->setParameter('profileId', $profileId)
            ->orderBy('m.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneById(string $id): ?Measurement
    {
        $entity = $this->find(strtolower($id));
        return $entity ?? $this->find($id);
    }

    public function findLatestByProfileId(string $profileId): ?Measurement
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.profile', 'p')
            ->andWhere('p.id = :profileId')
            ->setParameter('profileId', $profileId)
            ->orderBy('m.date', 'DESC')
            ->addOrderBy('m.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByProfileIdAndDate(string $profileId, \DateTimeImmutable $date): ?Measurement
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.profile', 'p')
            ->andWhere('p.id = :profileId')
            ->andWhere('m.date = :date')
            ->setParameter('profileId', $profileId)
            ->setParameter('date', $date)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
