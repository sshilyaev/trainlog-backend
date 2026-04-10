<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SupportRewardEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupportRewardEvent>
 */
final class SupportRewardEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportRewardEvent::class);
    }

    public function findByUserProviderAndExternalId(string $userId, string $adProvider, string $externalEventId): ?SupportRewardEvent
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.userId = :userId')
            ->andWhere('e.adProvider = :adProvider')
            ->andWhere('e.externalEventId = :externalEventId')
            ->setParameter('userId', $userId)
            ->setParameter('adProvider', $adProvider)
            ->setParameter('externalEventId', $externalEventId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByUserId(string $userId): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

