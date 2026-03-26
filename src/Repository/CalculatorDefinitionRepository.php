<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CalculatorDefinition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CalculatorDefinition>
 */
final class CalculatorDefinitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalculatorDefinition::class);
    }

    public function findOneByCalculatorId(string $calculatorId): ?CalculatorDefinition
    {
        $entity = $this->find($calculatorId);
        if ($entity instanceof CalculatorDefinition) {
            return $entity;
        }

        $entity = $this->find(strtolower($calculatorId));
        return $entity instanceof CalculatorDefinition ? $entity : null;
    }
}

