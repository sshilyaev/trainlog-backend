<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CoachTraineeLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CoachTraineeLink>
 */
final class CoachTraineeLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CoachTraineeLink::class);
    }

    /**
     * @return list<CoachTraineeLink>
     */
    public function findByCoachProfileId(string $coachProfileId): array
    {
        return $this->createQueryBuilder('l')
            ->innerJoin('l.coachProfile', 'c')
            ->andWhere('c.id = :id')
            ->setParameter('id', $coachProfileId)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<CoachTraineeLink>
     */
    public function findByTraineeProfileId(string $traineeProfileId): array
    {
        return $this->createQueryBuilder('l')
            ->innerJoin('l.traineeProfile', 't')
            ->andWhere('t.id = :id')
            ->setParameter('id', $traineeProfileId)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * All links where the profile is either coach or trainee.
     * @return list<CoachTraineeLink>
     */
    public function findByProfileId(string $profileId): array
    {
        $asCoach = $this->createQueryBuilder('l')
            ->innerJoin('l.coachProfile', 'c')
            ->andWhere('c.id = :id')
            ->setParameter('id', $profileId)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        $asTrainee = $this->createQueryBuilder('l')
            ->innerJoin('l.traineeProfile', 't')
            ->andWhere('t.id = :id')
            ->setParameter('id', $profileId)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        $ids = [];
        $result = [];
        foreach (array_merge($asCoach, $asTrainee) as $link) {
            if (!in_array($link->getId(), $ids, true)) {
                $ids[] = $link->getId();
                $result[] = $link;
            }
        }
        usort($result, fn (CoachTraineeLink $a, CoachTraineeLink $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        return $result;
    }

    public function findOneById(string $id): ?CoachTraineeLink
    {
        $entity = $this->find(strtolower($id));
        return $entity ?? $this->find($id);
    }

    public function existsLink(string $coachProfileId, string $traineeProfileId): bool
    {
        $count = (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->innerJoin('l.coachProfile', 'c')
            ->innerJoin('l.traineeProfile', 't')
            ->andWhere('c.id = :coachId')
            ->andWhere('t.id = :traineeId')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('traineeId', $traineeProfileId)
            ->getQuery()
            ->getSingleScalarResult();
        return $count > 0;
    }

    /**
     * Whether there is a link where trainee profile is the given one and the coach profile belongs to the given user (Firebase UID).
     */
    public function existsByTraineeProfileIdAndCoachUserId(string $traineeProfileId, string $coachUserId): bool
    {
        $count = (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->innerJoin('l.traineeProfile', 't')
            ->innerJoin('l.coachProfile', 'c')
            ->andWhere('t.id = :traineeId')
            ->andWhere('c.userId = :coachUserId')
            ->setParameter('traineeId', $traineeProfileId)
            ->setParameter('coachUserId', $coachUserId)
            ->getQuery()
            ->getSingleScalarResult();
        return $count > 0;
    }

    public function countActiveByCoachProfileId(string $coachProfileId): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->innerJoin('l.coachProfile', 'c')
            ->andWhere('c.id = :coachId')
            ->andWhere('l.archived = :archived')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('archived', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countNewLinksInMonth(string $coachProfileId, string $yearMonth): int
    {
        $start = \DateTimeImmutable::createFromFormat('!Y-m', $yearMonth);
        if ($start === false) {
            return 0;
        }
        $start = $start->setTime(0, 0);
        $end = $start->modify('+1 month');
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->innerJoin('l.coachProfile', 'c')
            ->andWhere('c.id = :coachId')
            ->andWhere('l.createdAt >= :start')
            ->andWhere('l.createdAt < :end')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
