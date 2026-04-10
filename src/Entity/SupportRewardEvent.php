<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'support_reward_event')]
#[ORM\UniqueConstraint(name: 'uniq_support_reward_event_user_provider_ext', columns: ['user_id', 'ad_provider', 'external_event_id'])]
#[ORM\Index(columns: ['user_id', 'created_at'], name: 'idx_support_reward_event_user_created')]
class SupportRewardEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(length: 128)]
    private string $userId;

    #[ORM\Column(length: 32)]
    private string $adProvider;

    #[ORM\Column(length: 191)]
    private string $externalEventId;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 1)]
    private string $rewardValueKg;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isDuplicate = false;

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

    public function getAdProvider(): string
    {
        return $this->adProvider;
    }

    public function setAdProvider(string $adProvider): self
    {
        $this->adProvider = $adProvider;

        return $this;
    }

    public function getExternalEventId(): string
    {
        return $this->externalEventId;
    }

    public function setExternalEventId(string $externalEventId): self
    {
        $this->externalEventId = $externalEventId;

        return $this;
    }

    public function getRewardValueKg(): float
    {
        return (float) $this->rewardValueKg;
    }

    public function setRewardValueKg(float $rewardValueKg): self
    {
        $this->rewardValueKg = number_format($rewardValueKg, 1, '.', '');

        return $this;
    }

    public function isDuplicate(): bool
    {
        return $this->isDuplicate;
    }

    public function setIsDuplicate(bool $isDuplicate): self
    {
        $this->isDuplicate = $isDuplicate;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

