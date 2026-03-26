<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'personal_record_metrics')]
#[ORM\Index(columns: ['record_id'], name: 'idx_personal_record_metrics_record')]
class PersonalRecordMetric
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: PersonalRecord::class, inversedBy: 'metrics')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private PersonalRecord $record;

    #[ORM\Column(length: 30)]
    private string $metricType;

    #[ORM\Column(type: Types::FLOAT)]
    private float $value;

    #[ORM\Column(length: 20)]
    private string $unit;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $displayOrder = 0;

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

    public function getRecord(): PersonalRecord
    {
        return $this->record;
    }

    public function setRecord(PersonalRecord $record): self
    {
        $this->record = $record;
        return $this;
    }

    public function getMetricType(): string
    {
        return $this->metricType;
    }

    public function setMetricType(string $metricType): self
    {
        $this->metricType = $metricType;
        return $this;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): self
    {
        $this->displayOrder = $displayOrder;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
