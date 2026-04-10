<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Visit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Visit>
 */
final class VisitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visit::class);
    }

    /**
     * @return list<Visit>
     */
    public function findByCoachAndTrainee(string $coachProfileId, string $traineeProfileId, ?string $yearMonth = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->innerJoin('v.coachProfile', 'c')
            ->innerJoin('v.traineeProfile', 't')
            ->andWhere('c.id = :coachId')
            ->andWhere('t.id = :traineeId')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('traineeId', $traineeProfileId)
            ->orderBy('v.date', 'DESC');
        if ($yearMonth !== null) {
            $start = \DateTimeImmutable::createFromFormat('!Y-m', $yearMonth);
            if ($start === false) {
                return [];
            }
            $start = $start->setTime(0, 0);
            $end = $start->modify('+1 month');
            $qb->andWhere('v.date >= :start')->andWhere('v.date < :end')
                ->setParameter('start', $start)->setParameter('end', $end);
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * Visits for coach–trainee or trainee-only with cursor pagination.
     *
     * @return array{0: list<Visit>, 1: ?string} [visits, nextCursor]
     */
    public function findPaginated(?string $coachProfileId, ?string $traineeProfileId, ?string $month, int $limit, ?string $after): array
    {
        $limit = min(max(1, $limit), 200);
        if ($coachProfileId !== null && $traineeProfileId !== null) {
            $qb = $this->createQueryBuilder('v')
                ->innerJoin('v.coachProfile', 'c')
                ->innerJoin('v.traineeProfile', 't')
                ->andWhere('c.id = :coachId')
                ->andWhere('t.id = :traineeId')
                ->setParameter('coachId', $coachProfileId)
                ->setParameter('traineeId', $traineeProfileId)
                ->orderBy('v.date', 'DESC')
                ->addOrderBy('v.id', 'DESC')
                ->setMaxResults($limit + 1);
            if ($after !== null && $after !== '') {
                $afterVisit = $this->findOneById($after);
                if ($afterVisit !== null) {
                    $qb->andWhere('(v.date < :afterDate OR (v.date = :afterDate AND v.id < :afterId))')
                        ->setParameter('afterDate', $afterVisit->getDate())
                        ->setParameter('afterId', $after);
                }
            }
            if ($month !== null && $month !== '') {
                $start = \DateTimeImmutable::createFromFormat('!Y-m', $month);
                if ($start !== false) {
                    $start = $start->setTime(0, 0);
                    $end = $start->modify('+1 month');
                    $qb->andWhere('v.date >= :start')->andWhere('v.date < :end')
                        ->setParameter('start', $start)->setParameter('end', $end);
                }
            }
            $results = $qb->getQuery()->getResult();
        } elseif ($traineeProfileId !== null) {
            $qb = $this->createQueryBuilder('v')
                ->innerJoin('v.traineeProfile', 't')
                ->andWhere('t.id = :id')
                ->setParameter('id', $traineeProfileId)
                ->orderBy('v.date', 'DESC')
                ->addOrderBy('v.id', 'DESC')
                ->setMaxResults($limit + 1);
            if ($after !== null && $after !== '') {
                $afterVisit = $this->findOneById($after);
                if ($afterVisit !== null) {
                    $qb->andWhere('(v.date < :afterDate OR (v.date = :afterDate AND v.id < :afterId))')
                        ->setParameter('afterDate', $afterVisit->getDate())
                        ->setParameter('afterId', $after);
                }
            }
            if ($month !== null && $month !== '') {
                $start = \DateTimeImmutable::createFromFormat('!Y-m', $month);
                if ($start !== false) {
                    $start = $start->setTime(0, 0);
                    $end = $start->modify('+1 month');
                    $qb->andWhere('v.date >= :start')->andWhere('v.date < :end')
                        ->setParameter('start', $start)->setParameter('end', $end);
                }
            }
            $results = $qb->getQuery()->getResult();
        } else {
            return [[], null];
        }
        $nextCursor = null;
        if (\count($results) > $limit) {
            $last = $results[$limit - 1];
            $nextCursor = $last->getId();
            $results = \array_slice($results, 0, $limit);
        }
        return [$results, $nextCursor];
    }

    /**
     * Visits for coach–trainee pair within date range (inclusive from, to).
     *
     * @return list<Visit>
     */
    public function findByCoachAndTraineeInPeriod(string $coachProfileId, string $traineeProfileId, string $from, string $to): array
    {
        $start = \DateTimeImmutable::createFromFormat('Y-m-d', $from);
        $end = \DateTimeImmutable::createFromFormat('Y-m-d', $to);
        if ($start === false || $end === false) {
            return [];
        }
        $start = $start->setTime(0, 0);
        $end = $end->setTime(23, 59, 59);
        return $this->createQueryBuilder('v')
            ->innerJoin('v.coachProfile', 'c')
            ->innerJoin('v.traineeProfile', 't')
            ->andWhere('c.id = :coachId')
            ->andWhere('t.id = :traineeId')
            ->andWhere('v.date >= :start')
            ->andWhere('v.date <= :end')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('traineeId', $traineeProfileId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('v.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * All visits for a trainee (any coach).
     * @return list<Visit>
     */
    public function findByTraineeProfileId(string $traineeProfileId, ?string $yearMonth = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->innerJoin('v.traineeProfile', 't')
            ->andWhere('t.id = :id')
            ->setParameter('id', $traineeProfileId)
            ->orderBy('v.date', 'DESC');
        if ($yearMonth !== null) {
            $start = \DateTimeImmutable::createFromFormat('!Y-m', $yearMonth);
            if ($start === false) {
                return [];
            }
            $start = $start->setTime(0, 0);
            $end = $start->modify('+1 month');
            $qb->andWhere('v.date >= :start')->andWhere('v.date < :end')
                ->setParameter('start', $start)->setParameter('end', $end);
        }
        return $qb->getQuery()->getResult();
    }

    public function findOneById(string $id): ?Visit
    {
        $entity = $this->find(strtolower($id));
        return $entity ?? $this->find($id);
    }

    public function countByCoachProfileIdInMonth(string $coachProfileId, string $yearMonth): int
    {
        $start = \DateTimeImmutable::createFromFormat('!Y-m', $yearMonth);
        if ($start === false) {
            return 0;
        }
        $start = $start->setTime(0, 0);
        $end = $start->modify('+1 month');
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->innerJoin('v.coachProfile', 'c')
            ->andWhere('c.id = :coachId')
            ->andWhere('v.date >= :start')
            ->andWhere('v.date < :end')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countTotalByCoachProfileId(string $coachProfileId): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->innerJoin('v.coachProfile', 'c')
            ->andWhere('c.id = :coachId')
            ->setParameter('coachId', $coachProfileId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Уникальные подопечные с хотя бы одним завершённым посещением в интервале [start, end).
     */
    public function countDistinctTraineesWithDoneVisitsInRange(
        string $coachProfileId,
        \DateTimeImmutable $rangeStart,
        \DateTimeImmutable $rangeEndExclusive,
    ): int {
        $start = $rangeStart->setTime(0, 0);
        $end = $rangeEndExclusive->setTime(0, 0);

        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(DISTINCT t.id)')
            ->innerJoin('v.coachProfile', 'c')
            ->innerJoin('v.traineeProfile', 't')
            ->andWhere('c.id = :coachId')
            ->andWhere('v.date >= :start')
            ->andWhere('v.date < :end')
            ->andWhere('v.status = :done')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('done', Visit::STATUS_DONE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Visit>
     */
    public function findByCoachInPeriod(string $coachProfileId, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $start = $from->setTime(0, 0);
        $end = $to->setTime(23, 59, 59);
        return $this->createQueryBuilder('v')
            ->innerJoin('v.coachProfile', 'c')
            ->andWhere('c.id = :coachId')
            ->andWhere('v.date >= :start')
            ->andWhere('v.date <= :end')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('v.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count visits in a month by:
     *  - bySubscription: done & has membership
     *  - oneTimePaid:    done, no membership, paymentStatus = paid
     *  - oneTimeDebt:    done, no membership, paymentStatus IN (debt, unpaid)
     *  - cancelled:      status = cancelled (any paymentStatus)
     *
     * Uses visit.date (not createdAt) and includes all coaches' trainees.
     *
     * @return array{bySubscription: int, oneTimePaid: int, oneTimeDebt: int, cancelled: int}
     */
    public function countByCoachProfileIdInMonthByType(string $coachProfileId, string $yearMonth): array
    {
        $start = \DateTimeImmutable::createFromFormat('!Y-m', $yearMonth);
        if ($start === false) {
            return [
                'bySubscription' => 0,
                'oneTimePaid' => 0,
                'oneTimeDebt' => 0,
                'cancelled' => 0,
            ];
        }
        $start = $start->setTime(0, 0);
        $end = $start->modify('+1 month');

        $bySubscription = (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->innerJoin('v.coachProfile', 'c')
            ->andWhere('c.id = :coachId')
            ->andWhere('v.date >= :start')
            ->andWhere('v.date < :end')
            ->andWhere('v.status = :done')
            ->andWhere('v.membership IS NOT NULL')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('done', Visit::STATUS_DONE)
            ->getQuery()
            ->getSingleScalarResult();

        $oneTimePaid = (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->innerJoin('v.coachProfile', 'c')
            ->andWhere('c.id = :coachId')
            ->andWhere('v.date >= :start')
            ->andWhere('v.date < :end')
            ->andWhere('v.status = :done')
            ->andWhere('v.membership IS NULL')
            ->andWhere('v.paymentStatus = :paid')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('done', Visit::STATUS_DONE)
            ->setParameter('paid', Visit::PAYMENT_PAID)
            ->getQuery()
            ->getSingleScalarResult();

        $oneTimeDebt = (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->innerJoin('v.coachProfile', 'c')
            ->andWhere('c.id = :coachId')
            ->andWhere('v.date >= :start')
            ->andWhere('v.date < :end')
            ->andWhere('v.status = :done')
            ->andWhere('v.membership IS NULL')
            ->andWhere('v.paymentStatus IN (:debtStatuses)')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('done', Visit::STATUS_DONE)
            ->setParameter('debtStatuses', [Visit::PAYMENT_DEBT, Visit::PAYMENT_UNPAID])
            ->getQuery()
            ->getSingleScalarResult();

        $cancelled = (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->innerJoin('v.coachProfile', 'c')
            ->andWhere('c.id = :coachId')
            ->andWhere('v.date >= :start')
            ->andWhere('v.date < :end')
            ->andWhere('v.status = :cancelled')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('cancelled', Visit::STATUS_CANCELLED)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'bySubscription' => $bySubscription,
            'oneTimePaid' => $oneTimePaid,
            'oneTimeDebt' => $oneTimeDebt,
            'cancelled' => $cancelled,
        ];
    }
}
