<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'calculator_definitions')]
final class CalculatorDefinition
{
    #[ORM\Id]
    #[ORM\Column(length: 50, name: 'calculator_id')]
    private string $calculatorId;

    #[ORM\Column(type: Types::JSON, columnDefinition: 'jsonb')]
    private array $definition = [];

    #[ORM\Column(type: Types::INTEGER)]
    private int $version = 1;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->calculatorId = '';
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCalculatorId(): string
    {
        return $this->calculatorId;
    }

    public function setCalculatorId(string $calculatorId): self
    {
        $this->calculatorId = $calculatorId;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefinition(): array
    {
        return $this->definition;
    }

    /**
     * JSON representation for admin UI editing/display.
     * EasyAdmin works better with strings than with array-to-JSONB conversions.
     */
    public function getDefinitionJson(): string
    {
        return (string) \json_encode($this->definition, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * @param array<string, mixed> $definition
     */
    public function setDefinition(array $definition): self
    {
        $this->definition = $definition;
        return $this;
    }

    /**
     * Admin helper: accept JSON string and set decoded definition array.
     *
     * @throws \InvalidArgumentException when JSON is invalid.
     */
    public function setDefinitionJson(string $definitionJson): self
    {
        $decoded = \json_decode($definitionJson, true);
        if (!\is_array($decoded)) {
            throw new \InvalidArgumentException('Invalid calculator definition JSON');
        }
        $this->definition = $decoded;
        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
}

