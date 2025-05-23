<?php

/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Entity;

use App\Interfaces\ComparableInterface;
use App\Interfaces\TimestampableInterface;
use App\Repository\CalculationStateRepository;
use App\Traits\TimestampableTrait;
use App\Utils\StringUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a calculation state.
 *
 * @implements ComparableInterface<CalculationState>
 */
#[ORM\Table(name: 'sy_CalculationState')]
#[ORM\Entity(repositoryClass: CalculationStateRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_calculation_state_code', columns: ['code'])]
#[UniqueEntity(fields: 'code', message: 'state.unique_code')]
class CalculationState extends AbstractEntity implements ComparableInterface, TimestampableInterface
{
    use TimestampableTrait;

    /**
     * The default color (black).
     */
    final public const DEFAULT_COLOR = '#000000';

    /**
     * The calculations.
     *
     * @var Collection<int, Calculation>
     *
     * @phpstan-var ArrayCollection<int, Calculation>
     */
    #[ORM\OneToMany(targetEntity: Calculation::class, mappedBy: 'state', fetch: self::EXTRA_LAZY)]
    private Collection $calculations;

    /**
     * The code (unique).
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_CODE_LENGTH)]
    #[ORM\Column(length: self::MAX_CODE_LENGTH, unique: true)]
    private ?string $code = null;

    /**
     * The color used in the user interface (UI).
     */
    #[Assert\CssColor]
    #[Assert\NotBlank]
    #[Assert\Length(max: 10)]
    #[ORM\Column(length: 10, options: ['default' => self::DEFAULT_COLOR])]
    private string $color = self::DEFAULT_COLOR;

    /**
     * The description.
     */
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    private ?string $description = null;

    /**
     * The editable state.
     */
    #[ORM\Column(options: ['default' => true])]
    private bool $editable = true;

    public function __construct()
    {
        $this->calculations = new ArrayCollection();
    }

    /**
     * Clone this calculation state.
     *
     * @param ?string $code the new code
     */
    public function clone(?string $code = null): self
    {
        $copy = clone $this;
        if (StringUtils::isString($code)) {
            $copy->setCode($code);
        }

        return $copy;
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        return \strnatcasecmp((string) $this->getCode(), (string) $other->getCode());
    }

    /**
     * Gets the number of calculations.
     */
    public function countCalculations(): int
    {
        return $this->calculations->count();
    }

    /**
     * Gets the calculations.
     *
     * @return Collection<int, Calculation>
     */
    public function getCalculations(): Collection
    {
        return $this->calculations;
    }

    /**
     * Get code.
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Get color as a hexadecimal value.
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * Get description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    #[\Override]
    public function getDisplay(): string
    {
        return (string) $this->getCode();
    }

    /**
     * Returns if this state contains one or more calculations.
     */
    public function hasCalculations(): bool
    {
        return 0 !== $this->calculations->count();
    }

    /**
     * Get a value indicating if calculations are editable.
     */
    public function isEditable(): bool
    {
        return $this->editable;
    }

    /**
     * Set code.
     */
    public function setCode(string $code): self
    {
        $this->code = StringUtils::trim($code);

        return $this;
    }

    /**
     * Set color as a hexadecimal value.
     */
    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Set description.
     */
    public function setDescription(?string $description): self
    {
        $this->description = StringUtils::trim($description);

        return $this;
    }

    /**
     * Set a value indicating if calculations are editable.
     */
    public function setEditable(bool $editable): self
    {
        $this->editable = $editable;

        return $this;
    }
}
