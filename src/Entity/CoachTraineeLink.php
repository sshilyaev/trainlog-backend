<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'coach_trainee_links')]
#[ORM\UniqueConstraint(name: 'uq_coach_trainee', columns: ['coach_profile_id', 'trainee_profile_id'])]
#[ORM\Index(columns: ['coach_profile_id'], name: 'idx_coach_trainee_links_coach')]
#[ORM\Index(columns: ['trainee_profile_id'], name: 'idx_coach_trainee_links_trainee')]
class CoachTraineeLink
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

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Отображаемое имя не должно быть длиннее {{ limit }} символов')]
    private ?string $displayName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $archived = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $favorite = false;

    public function __construct()
    {
        $this->id = (string) Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
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

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;
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

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): self
    {
        $this->archived = $archived;
        return $this;
    }

    public function isFavorite(): bool
    {
        return $this->favorite;
    }

    public function setFavorite(bool $favorite): self
    {
        $this->favorite = $favorite;
        return $this;
    }
}
