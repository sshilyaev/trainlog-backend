<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SupportCampaignState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupportCampaignState>
 */
final class SupportCampaignStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportCampaignState::class);
    }

    public function findByUserId(string $userId): ?SupportCampaignState
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.userId = :userId')
            ->setParameter('userId', $userId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

