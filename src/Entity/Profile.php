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
#[ORM\Table(name: 'profiles')]
#[ORM\Index(columns: ['user_id'], name: 'idx_profiles_user_id')]
#[ORM\Index(columns: ['type'], name: 'idx_profiles_type')]
class Profile
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(length: 128)]
    private string $userId;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: ValidationMessage::ProfileTypeRequired->value)]
    #[Assert\Choice(choices: [self::TYPE_COACH, self::TYPE_TRAINEE], message: ValidationMessage::ProfileTypeCoachOrTrainee->value)]
    private string $type;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: ValidationMessage::NameRequired->value)]
    #[Assert\Length(max: 255, maxMessage: 'Имя не должно быть длиннее {{ limit }} символов')]
    private string $name;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Название зала не должно быть длиннее {{ limit }} символов')]
    private ?string $gymName = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(choices: [self::GENDER_MALE, self::GENDER_FEMALE], message: ValidationMessage::GenderMaleOrFemale->value)]
    private ?string $gender = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $iconEmoji = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $developerMode = false;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateOfBirth = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $telegramUsername = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $height = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $weight = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $weightUpdatedAt = null;

    #[ORM\Column(length: 36, nullable: true)]
    private ?string $ownerCoachProfileId = null;

    #[ORM\Column(length: 36, nullable: true)]
    private ?string $mergedIntoProfileId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $mergedAt = null;

    /** @var Collection<int, CoachTraineeLink> */
    #[ORM\OneToMany(targetEntity: CoachTraineeLink::class, mappedBy: 'coachProfile')]
    private Collection $linksAsCoach;

    /** @var Collection<int, CoachTraineeLink> */
    #[ORM\OneToMany(targetEntity: CoachTraineeLink::class, mappedBy: 'traineeProfile')]
    private Collection $linksAsTrainee;

    public const TYPE_COACH = 'coach';
    public const TYPE_TRAINEE = 'trainee';

    public const GENDER_MALE = 'male';
    public const GENDER_FEMALE = 'female';

    public function __construct()
    {
        $this->id = (string) Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->linksAsCoach = new ArrayCollection();
        $this->linksAsTrainee = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name . ' (' . $this->type . ')';
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getGymName(): ?string
    {
        return $this->gymName;
    }

    public function setGymName(?string $gymName): self
    {
        $this->gymName = $gymName;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    public function getIconEmoji(): ?string
    {
        return $this->iconEmoji;
    }

    public function setIconEmoji(?string $iconEmoji): self
    {
        $this->iconEmoji = $iconEmoji;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isDeveloperMode(): bool
    {
        return $this->developerMode;
    }

    public function setDeveloperMode(bool $developerMode): self
    {
        $this->developerMode = $developerMode;
        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeImmutable
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTimeImmutable $dateOfBirth): self
    {
        $this->dateOfBirth = $dateOfBirth;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getTelegramUsername(): ?string
    {
        return $this->telegramUsername;
    }

    public function setTelegramUsername(?string $telegramUsername): self
    {
        $this->telegramUsername = $telegramUsername;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getHeight(): ?float
    {
        return $this->height;
    }

    public function setHeight(?float $height): self
    {
        $this->height = $height;
        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    public function getWeightUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->weightUpdatedAt;
    }

    public function setWeightUpdatedAt(?\DateTimeImmutable $weightUpdatedAt): self
    {
        $this->weightUpdatedAt = $weightUpdatedAt;
        return $this;
    }

    public function getOwnerCoachProfileId(): ?string
    {
        return $this->ownerCoachProfileId;
    }

    public function setOwnerCoachProfileId(?string $ownerCoachProfileId): self
    {
        $this->ownerCoachProfileId = $ownerCoachProfileId;
        return $this;
    }

    public function getMergedIntoProfileId(): ?string
    {
        return $this->mergedIntoProfileId;
    }

    public function setMergedIntoProfileId(?string $mergedIntoProfileId): self
    {
        $this->mergedIntoProfileId = $mergedIntoProfileId;
        return $this;
    }

    public function getMergedAt(): ?\DateTimeImmutable
    {
        return $this->mergedAt;
    }

    public function setMergedAt(?\DateTimeImmutable $mergedAt): self
    {
        $this->mergedAt = $mergedAt;
        return $this;
    }

    /** @return Collection<int, CoachTraineeLink> */
    public function getLinksAsCoach(): Collection
    {
        return $this->linksAsCoach;
    }

    /** @return Collection<int, CoachTraineeLink> */
    public function getLinksAsTrainee(): Collection
    {
        return $this->linksAsTrainee;
    }
}
