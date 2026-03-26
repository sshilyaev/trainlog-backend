<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'measurements')]
#[ORM\Index(columns: ['profile_id', 'date'], name: 'idx_measurements_profile_date')]
class Measurement
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Profile $profile;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $date;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $weight = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $height = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $neck = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $shoulders = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $leftBiceps = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $rightBiceps = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $waist = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $belly = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $chest = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $leftThigh = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $rightThigh = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $hips = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $buttocks = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $leftCalf = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $rightCalf = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

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

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;
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

    public function getHeight(): ?float
    {
        return $this->height;
    }

    public function setHeight(?float $height): self
    {
        $this->height = $height;
        return $this;
    }

    public function getNeck(): ?float
    {
        return $this->neck;
    }

    public function setNeck(?float $neck): self
    {
        $this->neck = $neck;
        return $this;
    }

    public function getShoulders(): ?float
    {
        return $this->shoulders;
    }

    public function setShoulders(?float $shoulders): self
    {
        $this->shoulders = $shoulders;
        return $this;
    }

    public function getLeftBiceps(): ?float
    {
        return $this->leftBiceps;
    }

    public function setLeftBiceps(?float $leftBiceps): self
    {
        $this->leftBiceps = $leftBiceps;
        return $this;
    }

    public function getRightBiceps(): ?float
    {
        return $this->rightBiceps;
    }

    public function setRightBiceps(?float $rightBiceps): self
    {
        $this->rightBiceps = $rightBiceps;
        return $this;
    }

    public function getWaist(): ?float
    {
        return $this->waist;
    }

    public function setWaist(?float $waist): self
    {
        $this->waist = $waist;
        return $this;
    }

    public function getBelly(): ?float
    {
        return $this->belly;
    }

    public function setBelly(?float $belly): self
    {
        $this->belly = $belly;
        return $this;
    }

    public function getChest(): ?float
    {
        return $this->chest;
    }

    public function setChest(?float $chest): self
    {
        $this->chest = $chest;
        return $this;
    }

    public function getLeftThigh(): ?float
    {
        return $this->leftThigh;
    }

    public function setLeftThigh(?float $leftThigh): self
    {
        $this->leftThigh = $leftThigh;
        return $this;
    }

    public function getRightThigh(): ?float
    {
        return $this->rightThigh;
    }

    public function setRightThigh(?float $rightThigh): self
    {
        $this->rightThigh = $rightThigh;
        return $this;
    }

    public function getHips(): ?float
    {
        return $this->hips;
    }

    public function setHips(?float $hips): self
    {
        $this->hips = $hips;
        return $this;
    }

    public function getButtocks(): ?float
    {
        return $this->buttocks;
    }

    public function setButtocks(?float $buttocks): self
    {
        $this->buttocks = $buttocks;
        return $this;
    }

    public function getLeftCalf(): ?float
    {
        return $this->leftCalf;
    }

    public function setLeftCalf(?float $leftCalf): self
    {
        $this->leftCalf = $leftCalf;
        return $this;
    }

    public function getRightCalf(): ?float
    {
        return $this->rightCalf;
    }

    public function setRightCalf(?float $rightCalf): self
    {
        $this->rightCalf = $rightCalf;
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
}
