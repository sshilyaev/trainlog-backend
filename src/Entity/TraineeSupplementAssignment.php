<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'trainee_supplement_assignments')]
#[ORM\UniqueConstraint(name: 'uq_trainee_supplements_coach_trainee_supplement', columns: ['coach_profile_id', 'trainee_profile_id', 'supplement_id'])]
#[ORM\Index(columns: ['coach_profile_id'], name: 'idx_trainee_supplements_coach')]
#[ORM\Index(columns: ['trainee_profile_id'], name: 'idx_trainee_supplements_trainee')]
#[ORM\Index(columns: ['supplement_id'], name: 'idx_trainee_supplements_supplement')]
final class TraineeSupplementAssignment
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

    #[ORM\ManyToOne(targetEntity: SupplementCatalog::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private SupplementCatalog $supplement;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $dosage = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $dosageUnit = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $timing = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $frequency = null;

    #[ORM\Column(type: Types::STRING, length: 1000, nullable: true)]
    private ?string $note = null;

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

    public function getSupplement(): SupplementCatalog
    {
        return $this->supplement;
    }

    public function setSupplement(SupplementCatalog $supplement): self
    {
        $this->supplement = $supplement;
        return $this;
    }

    public function getDosage(): ?string
    {
        return $this->dosage;
    }

    public function setDosage(?string $dosage): self
    {
        $this->dosage = $dosage;
        return $this;
    }

    public function getDosageUnit(): ?string
    {
        return $this->dosageUnit;
    }

    public function setDosageUnit(?string $dosageUnit): self
    {
        $this->dosageUnit = $dosageUnit;
        return $this;
    }

    public function getTiming(): ?string
    {
        return $this->timing;
    }

    public function setTiming(?string $timing): self
    {
        $this->timing = $timing;
        return $this;
    }

    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    public function setFrequency(?string $frequency): self
    {
        $this->frequency = $frequency;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
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

