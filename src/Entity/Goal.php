<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ValidationMessage;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'goals')]
#[ORM\Index(columns: ['profile_id', 'target_date'], name: 'idx_goals_profile_target_date')]
class Goal
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Profile $profile;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: ValidationMessage::MeasurementTypeRequired->value)]
    #[Assert\Length(max: 50, maxMessage: 'Тип замера не должен быть длиннее {{ limit }} символов')]
    private string $measurementType;

    #[ORM\Column(type: Types::FLOAT)]
    #[Assert\NotNull(message: ValidationMessage::TargetValueRequired->value)]
    private float $targetValue;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull(message: ValidationMessage::TargetDateRequired->value)]
    private \DateTimeImmutable $targetDate;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = (string) Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function setProfile(Profile $profile): self
    {
        $this->profile = $profile;
        return $this;
    }

    public function getMeasurementType(): string
    {
        return $this->measurementType;
    }

    public function setMeasurementType(string $measurementType): self
    {
        $this->measurementType = $measurementType;
        return $this;
    }

    public function getTargetValue(): float
    {
        return $this->targetValue;
    }

    public function setTargetValue(float $targetValue): self
    {
        $this->targetValue = $targetValue;
        return $this;
    }

    public function getTargetDate(): \DateTimeImmutable
    {
        return $this->targetDate;
    }

    public function setTargetDate(\DateTimeImmutable $targetDate): self
    {
        $this->targetDate = $targetDate;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
