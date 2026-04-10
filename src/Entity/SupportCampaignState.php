<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'support_campaign_state')]
#[ORM\Index(columns: ['user_id'], name: 'idx_support_campaign_state_user')]
class SupportCampaignState
{
    public const GOAL_LOSE = 'lose_weight';
    public const GOAL_GAIN = 'gain_weight';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(length: 128)]
    private string $userId;

    #[ORM\Column(length: 20)]
    private string $goalType = self::GOAL_LOSE;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 1)]
    private string $startWeightKg = '0.0';

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 1)]
    private string $currentWeightKg = '0.0';

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 1)]
    private string $targetWeightKg = '0.0';

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: Types::INTEGER)]
    private int $savedClientsCount = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = (string) Uuid::v4();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getGoalType(): string
    {
        return $this->goalType;
    }

    public function setGoalType(string $goalType): self
    {
        $this->goalType = $goalType;

        return $this;
    }

    public function getStartWeightKg(): float
    {
        return (float) $this->startWeightKg;
    }

    public function setStartWeightKg(float $startWeightKg): self
    {
        $this->startWeightKg = number_format($startWeightKg, 1, '.', '');

        return $this;
    }

    public function getCurrentWeightKg(): float
    {
        return (float) $this->currentWeightKg;
    }

    public function setCurrentWeightKg(float $currentWeightKg): self
    {
        $this->currentWeightKg = number_format($currentWeightKg, 1, '.', '');

        return $this;
    }

    public function getTargetWeightKg(): float
    {
        return (float) $this->targetWeightKg;
    }

    public function setTargetWeightKg(float $targetWeightKg): self
    {
        $this->targetWeightKg = number_format($targetWeightKg, 1, '.', '');

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSavedClientsCount(): int
    {
        return $this->savedClientsCount;
    }

    public function setSavedClientsCount(int $savedClientsCount): self
    {
        $this->savedClientsCount = max(0, $savedClientsCount);

        return $this;
    }

    public function incrementSavedClientsCount(): self
    {
        ++$this->savedClientsCount;

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

