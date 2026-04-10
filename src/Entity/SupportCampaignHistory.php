<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'support_campaign_history')]
#[ORM\Index(columns: ['user_id', 'created_at'], name: 'idx_support_campaign_history_user_created')]
class SupportCampaignHistory
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(length: 128)]
    private string $userId;

    #[ORM\Column(length: 20)]
    private string $goalType;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 1)]
    private string $startWeightKg;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 1)]
    private string $targetWeightKg;

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

    public function getTargetWeightKg(): float
    {
        return (float) $this->targetWeightKg;
    }

    public function setTargetWeightKg(float $targetWeightKg): self
    {
        $this->targetWeightKg = number_format($targetWeightKg, 1, '.', '');

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

