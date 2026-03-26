<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
final class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /** @return list<Event> */
    public function findByCoachAndTrainee(string $coachProfileId, string $traineeProfileId): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.coachProfile', 'c')
            ->innerJoin('e.traineeProfile', 't')
            ->andWhere('c.id = :coachId')
            ->andWhere('t.id = :traineeId')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('traineeId', $traineeProfileId)
            ->orderBy('e.date', 'DESC')
            ->addOrderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Events for coach–trainee pair within date range (inclusive from, to).
     *
     * @return list<Event>
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
        return $this->createQueryBuilder('e')
            ->innerJoin('e.coachProfile', 'c')
            ->innerJoin('e.traineeProfile', 't')
            ->andWhere('c.id = :coachId')
            ->andWhere('t.id = :traineeId')
            ->andWhere('e.date >= :start')
            ->andWhere('e.date <= :end')
            ->setParameter('coachId', $coachProfileId)
            ->setParameter('traineeId', $traineeProfileId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('e.date', 'DESC')
            ->addOrderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Event> */
    public function findByProfileId(string $profileId): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.coachProfile', 'c')
            ->innerJoin('e.traineeProfile', 't')
            ->andWhere('c.id = :profileId OR t.id = :profileId')
            ->setParameter('profileId', $profileId)
            ->orderBy('e.date', 'DESC')
            ->addOrderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Events for coach–trainee pair or one profile with cursor pagination.
     *
     * @return array{0: list<Event>, 1: ?string} [events, nextCursor]
     */
    public function findPaginated(?string $coachProfileId, ?string $traineeProfileId, ?string $profileId, int $limit, ?string $after): array
    {
        $limit = min(max(1, $limit), 200);
        if ($profileId !== null && $profileId !== '') {
            $qb = $this->createQueryBuilder('e')
                ->innerJoin('e.coachProfile', 'c')
                ->innerJoin('e.traineeProfile', 't')
                ->andWhere('c.id = :profileId OR t.id = :profileId')
                ->setParameter('profileId', $profileId)
                ->orderBy('e.date', 'DESC')
                ->addOrderBy('e.id', 'DESC')
                ->setMaxResults($limit + 1);
        } elseif ($coachProfileId !== null && $traineeProfileId !== null) {
            $qb = $this->createQueryBuilder('e')
                ->innerJoin('e.coachProfile', 'c')
                ->innerJoin('e.traineeProfile', 't')
                ->andWhere('c.id = :coachId')
                ->andWhere('t.id = :traineeId')
                ->setParameter('coachId', $coachProfileId)
                ->setParameter('traineeId', $traineeProfileId)
                ->orderBy('e.date', 'DESC')
                ->addOrderBy('e.id', 'DESC')
                ->setMaxResults($limit + 1);
        } else {
            return [[], null];
        }
        if ($after !== null && $after !== '') {
            $afterEvent = $this->findOneById($after);
            if ($afterEvent !== null) {
                $qb->andWhere('(e.date < :afterDate OR (e.date = :afterDate AND e.id < :afterId))')
                    ->setParameter('afterDate', $afterEvent->getDate())
                    ->setParameter('afterId', $after);
            }
        }
        $results = $qb->getQuery()->getResult();
        $nextCursor = null;
        if (\count($results) > $limit) {
            $last = $results[$limit - 1];
            $nextCursor = $last->getId();
            $results = \array_slice($results, 0, $limit);
        }
        return [$results, $nextCursor];
    }

    public function findOneById(string $id): ?Event
    {
        $entity = $this->find(strtolower($id));
        return $entity ?? $this->find($id);
    }
}
