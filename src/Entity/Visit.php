<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'visits')]
#[ORM\Index(columns: ['coach_profile_id', 'date'], name: 'idx_visits_coach_date')]
#[ORM\Index(columns: ['trainee_profile_id'], name: 'idx_visits_trainee')]
#[ORM\Index(columns: ['membership_id'], name: 'idx_visits_membership')]
class Visit
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

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $date;

    #[ORM\Column(length: 20)]
    private string $status;

    #[ORM\Column(length: 20)]
    private string $paymentStatus;

    #[ORM\ManyToOne(targetEntity: Membership::class, inversedBy: 'visits')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Membership $membership = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $membershipDisplayCode = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    public const STATUS_PLANNED = 'planned';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'noShow';

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_DEBT = 'debt';

    public function __construct()
    {
        $this->id = (string) Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
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

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;
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

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): self
    {
        $this->cancelledAt = $cancelledAt;
        return $this;
    }

    public function getMembership(): ?Membership
    {
        return $this->membership;
    }

    public function setMembership(?Membership $membership): self
    {
        $this->membership = $membership;
        return $this;
    }

    public function getMembershipDisplayCode(): ?string
    {
        return $this->membershipDisplayCode;
    }

    public function setMembershipDisplayCode(?string $membershipDisplayCode): self
    {
        $this->membershipDisplayCode = $membershipDisplayCode;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
