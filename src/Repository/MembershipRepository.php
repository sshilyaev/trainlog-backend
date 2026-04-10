<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Membership;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Membership>
 */
final class MembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Membership::class);
    }

    /**
     * @return list<Membership>
     */
    public function findByCoachProfileId(string $coachProfileId, ?string $traineeProfileId = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->innerJoin('m.coachProfile', 'c')
            ->andWhere('c.id = :coachId')
            ->setParameter('coachId', $coachProfileId)
            ->orderBy('m.createdAt', 'DESC');
        if ($traineeProfileId !== null) {
            $qb->innerJoin('m.traineeProfile', 't')
                ->andWhere('t.id = :traineeId')
                ->setParameter('traineeId', $traineeProfileId);
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<Membership>
     */
    public function findByTraineeProfileId(string $traineeProfileId): array
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.traineeProfile', 't')
            ->andWhere('t.id = :id')
            ->setParameter('id', $traineeProfileId)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneById(string $id): ?Membership
    {
        $entity = $this->find(strtolower($id));
        return $entity ?? $this->find($id);
    }

    /**
     * Следующий номер абонемента для данного подопечного: по посещениям — А1, А2…; безлимитный — Б1, Б2…
     */
    public function getNextDisplayCodeForTrainee(string $coachProfileId, string $traineeProfileId, string $kind): string
    {
        $count = (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->innerJoin('m.coachProfile', 'c')
            ->innerJoin('m.traineeProfile', 't')
            ->andWhere('c.id = :coachId')
            ->andWhere('t.id = :traineeId')
            ->andWhere('m.kind = :kind')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('traineeId', $traineeProfileId)
            ->setParameter('kind', $kind)
            ->getQuery()
            ->getSingleScalarResult();
        $n = $count + 1;
        return $kind === Membership::KIND_UNLIMITED ? 'Б' . $n : 'А' . $n;
    }

    /**
     * Active memberships for this coach-trainee pair with at least one session left
     * (by visits: used < total; unlimited: today within start_date..end_date+freeze_days).
     * @return list<Membership>
     */
    public function findActiveWithRemainderForPair(string $coachProfileId, string $traineeProfileId): array
    {
        $all = $this->createQueryBuilder('m')
            ->innerJoin('m.coachProfile', 'c')
            ->innerJoin('m.traineeProfile', 't')
            ->andWhere('c.id = :coachId')
            ->andWhere('t.id = :traineeId')
            ->andWhere('m.status = :status')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('traineeId', $traineeProfileId)
            ->setParameter('status', Membership::STATUS_ACTIVE)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
        return array_values(array_filter($all, fn (Membership $m) => $m->hasRemainingSessions()));
    }

    public function countActiveByCoachProfileId(string $coachProfileId): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->innerJoin('m.coachProfile', 'c')
            ->andWhere('c.id = :coachId')
            ->andWhere('m.status = :status')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('status', Membership::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Active memberships count by kind: unlimited and by_visits.
     *
     * @return array{unlimited: int, byVisits: int}
     */
    public function countActiveByCoachProfileIdByKind(string $coachProfileId): array
    {
        $unlimited = (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->innerJoin('m.coachProfile', 'c')
            ->andWhere('c.id = :coachId')
            ->andWhere('m.status = :status')
            ->andWhere('m.kind = :kind')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('status', Membership::STATUS_ACTIVE)
            ->setParameter('kind', Membership::KIND_UNLIMITED)
            ->getQuery()
            ->getSingleScalarResult();

        $byVisits = (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->innerJoin('m.coachProfile', 'c')
            ->andWhere('c.id = :coachId')
            ->andWhere('m.status = :status')
            ->andWhere('m.kind = :kind')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('status', Membership::STATUS_ACTIVE)
            ->setParameter('kind', Membership::KIND_BY_VISITS)
            ->getQuery()
            ->getSingleScalarResult();

        return ['unlimited' => $unlimited, 'byVisits' => $byVisits];
    }

    /**
     * Active memberships ending soon: 1–2 sessions left (by_visits) or end_date within 14 days (unlimited).
     */
    /**
     * Абонементы, у которых createdAt попадает в [start, end).
     */
    public function countCreatedByCoachProfileIdInRange(
        string $coachProfileId,
        \DateTimeImmutable $rangeStart,
        \DateTimeImmutable $rangeEndExclusive,
    ): int {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->innerJoin('m.coachProfile', 'c')
            ->andWhere('c.id = :coachId')
            ->andWhere('m.createdAt >= :start')
            ->andWhere('m.createdAt < :end')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('start', $rangeStart)
            ->setParameter('end', $rangeEndExclusive)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countEndingSoonByCoachProfileId(string $coachProfileId): int
    {
        $in14Days = (new \DateTimeImmutable('today'))->modify('+14 days');
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->innerJoin('m.coachProfile', 'c')
            ->andWhere('c.id = :coachId')
            ->andWhere('m.status = :status')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('status', Membership::STATUS_ACTIVE)
            ->andWhere(
                '(m.kind = :byVisits AND (m.totalSessions - m.usedSessions) <= 2) OR (m.kind = :unlimited AND m.endDate IS NOT NULL AND m.endDate <= :in14Days)'
            )
            ->setParameter('byVisits', Membership::KIND_BY_VISITS)
            ->setParameter('unlimited', Membership::KIND_UNLIMITED)
            ->setParameter('in14Days', $in14Days);
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
