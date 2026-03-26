<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ValidationMessage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'memberships')]
#[ORM\Index(columns: ['coach_profile_id'], name: 'idx_memberships_coach')]
#[ORM\Index(columns: ['trainee_profile_id'], name: 'idx_memberships_trainee')]
#[ORM\Index(columns: ['coach_profile_id', 'trainee_profile_id', 'status'], name: 'idx_memberships_coach_trainee_status')]
class Membership
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

    #[ORM\Column(length: 20)]
    private string $kind = self::KIND_BY_VISITS;

    #[ORM\Column(type: Types::INTEGER)]
    private int $totalSessions;

    #[ORM\Column(type: Types::INTEGER)]
    private int $usedSessions = 0;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $freezeDays = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $priceRub = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::STATUS_ACTIVE, self::STATUS_FINISHED, self::STATUS_CANCELLED], message: ValidationMessage::StatusActiveFinishedCancelled->value)]
    private string $status;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $displayCode = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Visit> */
    #[ORM\OneToMany(targetEntity: Visit::class, mappedBy: 'membership')]
    private Collection $visits;

    public const KIND_BY_VISITS = 'by_visits';
    public const KIND_UNLIMITED = 'unlimited';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_CANCELLED = 'cancelled';

    public function __construct()
    {
        $this->id = (string) Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->visits = new ArrayCollection();
    }

    public function __toString(): string
    {
        $parts = [];
        if ($this->displayCode !== null && $this->displayCode !== '') {
            $parts[] = $this->displayCode;
        }
        $parts[] = $this->traineeProfile->getName() . ' — ' . $this->coachProfile->getName();
        $parts[] = '(' . $this->status . ')';
        return implode(' ', $parts);
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

    public function getTotalSessions(): int
    {
        return $this->totalSessions;
    }

    public function setTotalSessions(int $totalSessions): self
    {
        $this->totalSessions = $totalSessions;
        return $this;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function setKind(string $kind): self
    {
        $this->kind = $kind;
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getFreezeDays(): int
    {
        return $this->freezeDays;
    }

    public function setFreezeDays(int $freezeDays): self
    {
        $this->freezeDays = $freezeDays;
        return $this;
    }

    public function getUsedSessions(): int
    {
        return $this->usedSessions;
    }

    public function setUsedSessions(int $usedSessions): self
    {
        $this->usedSessions = $usedSessions;
        return $this;
    }

    public function getPriceRub(): ?int
    {
        return $this->priceRub;
    }

    public function setPriceRub(?int $priceRub): self
    {
        $this->priceRub = $priceRub;
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

    public function getDisplayCode(): ?string
    {
        return $this->displayCode;
    }

    public function setDisplayCode(?string $displayCode): self
    {
        $this->displayCode = $displayCode;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, Visit> */
    public function getVisits(): Collection
    {
        return $this->visits;
    }

    public function hasRemainingSessions(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        if ($this->kind === self::KIND_BY_VISITS) {
            return $this->usedSessions < $this->totalSessions;
        }
        if ($this->kind === self::KIND_UNLIMITED && $this->startDate !== null && $this->endDate !== null) {
            $effectiveEnd = $this->endDate->modify('+' . $this->freezeDays . ' days');
            $now = new \DateTimeImmutable('today');
            return $now >= $this->startDate && $now <= $effectiveEnd;
        }
        return false;
    }

    public function getEffectiveEndDate(): ?\DateTimeImmutable
    {
        if ($this->endDate === null) {
            return null;
        }
        return $this->endDate->modify('+' . $this->freezeDays . ' days');
    }
}
