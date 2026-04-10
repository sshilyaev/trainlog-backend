<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SupportCampaignHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupportCampaignHistory>
 */
final class SupportCampaignHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportCampaignHistory::class);
    }

    /** @return list<SupportCampaignHistory> */
    public function findRecentByUserId(string $userId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));

        return $this->createQueryBuilder('h')
            ->andWhere('h.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

