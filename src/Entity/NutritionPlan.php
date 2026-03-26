<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'nutrition_plans')]
#[ORM\UniqueConstraint(name: 'uq_nutrition_plans_coach_trainee', columns: ['coach_profile_id', 'trainee_profile_id'])]
#[ORM\Index(columns: ['coach_profile_id'], name: 'idx_nutrition_plans_coach')]
#[ORM\Index(columns: ['trainee_profile_id'], name: 'idx_nutrition_plans_trainee')]
class NutritionPlan
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Profile $coachProfile;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Profile $traineeProfile;

    #[ORM\Column(type: Types::FLOAT)]
    private float $weightKgUsed;

    #[ORM\Column(type: Types::FLOAT)]
    private float $proteinPerKg;

    #[ORM\Column(type: Types::FLOAT)]
    private float $fatPerKg;

    #[ORM\Column(type: Types::FLOAT)]
    private float $carbsPerKg;

    #[ORM\Column(type: Types::FLOAT)]
    private float $proteinGrams;

    #[ORM\Column(type: Types::FLOAT)]
    private float $fatGrams;

    #[ORM\Column(type: Types::FLOAT)]
    private float $carbsGrams;

    #[ORM\Column(type: Types::INTEGER)]
    private int $calories;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = (string) Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCoachProfile(): Profile
    {
        return $this->coachProfile;
    }

    public function setCoachProfile(Profile $coachProfile): self
    {
        $this->coachProfile = $coachProfile;
        return $this;
    }

    public function getTraineeProfile(): Profile
    {
        return $this->traineeProfile;
    }

    public function setTraineeProfile(Profile $traineeProfile): self
    {
        $this->traineeProfile = $traineeProfile;
        return $this;
    }

    public function getWeightKgUsed(): float
    {
        return $this->weightKgUsed;
    }

    public function setWeightKgUsed(float $weightKgUsed): self
    {
        $this->weightKgUsed = $weightKgUsed;
        return $this;
    }

    public function getProteinPerKg(): float
    {
        return $this->proteinPerKg;
    }

    public function setProteinPerKg(float $proteinPerKg): self
    {
        $this->proteinPerKg = $proteinPerKg;
        return $this;
    }

    public function getFatPerKg(): float
    {
        return $this->fatPerKg;
    }

    public function setFatPerKg(float $fatPerKg): self
    {
        $this->fatPerKg = $fatPerKg;
        return $this;
    }

    public function getCarbsPerKg(): float
    {
        return $this->carbsPerKg;
    }

    public function setCarbsPerKg(float $carbsPerKg): self
    {
        $this->carbsPerKg = $carbsPerKg;
        return $this;
    }

    public function getProteinGrams(): float
    {
        return $this->proteinGrams;
    }

    public function setProteinGrams(float $proteinGrams): self
    {
        $this->proteinGrams = $proteinGrams;
        return $this;
    }

    public function getFatGrams(): float
    {
        return $this->fatGrams;
    }

    public function setFatGrams(float $fatGrams): self
    {
        $this->fatGrams = $fatGrams;
        return $this;
    }

    public function getCarbsGrams(): float
    {
        return $this->carbsGrams;
    }

    public function setCarbsGrams(float $carbsGrams): self
    {
        $this->carbsGrams = $carbsGrams;
        return $this;
    }

    public function getCalories(): int
    {
        return $this->calories;
    }

    public function setCalories(int $calories): self
    {
        $this->calories = $calories;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
}

