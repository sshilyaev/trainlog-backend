<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Calculator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Calculator>
 */
final class CalculatorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Calculator::class);
    }

    /**
     * @return list<Calculator>
     */
    public function findEnabledOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isEnabled = true')
            ->orderBy('c.order', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneById(string $id): ?Calculator
    {
        $entity = $this->find($id);
        if ($entity !== null) {
            return $entity;
        }

        // Some clients might send different casing.
        $entity = $this->find(strtolower($id));
        return $entity instanceof Calculator ? $entity : null;
    }
}

