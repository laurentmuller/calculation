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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a calculation state.
 *
 * @author Laurent Muller
 *
 * @ORM\Entity(repositoryClass="App\Repository\CalculationStateRepository")
 * @ORM\Table(name="sy_CalculationState")
 * @UniqueEntity(fields="code", message="state.unique_code")
 */
class CalculationState extends AbstractEntity
{
    /**
     * The default color (black).
     */
    final public const DEFAULT_COLOR = '#000000';

    /**
     * The code (unique).
     *
     * @ORM\Column(type="string", length=30, unique=true)
     * @Assert\NotBlank
     * @Assert\Length(max=30)
     */
    protected ?string $code = null;

    /**
     * The color used in the user interface (UI).
     *
     * @ORM\Column(type="string", length=10, options={"default" = "#000000"})
     * @Assert\CssColor
     * @Assert\NotBlank
     * @Assert\Length(max=10)
     */
    protected string $color = self::DEFAULT_COLOR;

    /**
     * The description.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    protected ?string $description = null;

    /**
     * The editable state.
     *
     * @ORM\Column(type="boolean", options={"default" = true})
     */
    protected bool $editable = true;

    /**
     * The list of calculations that fall into this category.
     *
     * @ORM\OneToMany(targetEntity=Calculation::class, mappedBy="state")
     *
     * @var Calculation[]|Collection
     * @psalm-var Collection<int, Calculation>
     */
    private Collection $calculations;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->calculations = new ArrayCollection();
    }

    /**
     * Clone this calculation state.
     *
     * @param string|null $code the new code
     */
    public function clone(?string $code = null): self
    {
        /** @var CalculationState $copy */
        $copy = clone $this;

        if ($code) {
            $copy->setCode($code);
        }

        return $copy;
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

    /**
     * {@inheritdoc}
     */
    public function getDisplay(): string
    {
        return (string) $this->getCode();
    }

    /**
     * Returns if this state contains one or more calculations.
     */
    public function hasCalculations(): bool
    {
        return !$this->calculations->isEmpty();
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
        $this->code = $this->trim($code);

        return $this;
    }

    /**
     * Set color as a hexadecimal value.
     */
    public function setColor(string $color): self
    {
        $this->color = (string) $this->trim($color);

        return $this;
    }

    /**
     * Set description.
     */
    public function setDescription(?string $description): self
    {
        $this->description = $this->trim($description);

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

    /**
     * {@inheritdoc}
     */
    protected function getSearchTerms(): array
    {
        return [
            $this->code,
            $this->description,
        ];
    }
}
