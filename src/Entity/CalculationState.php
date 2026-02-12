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

use App\Repository\CalculationStateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use fpdf\Color\PdfRgbColor;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a calculation state.
 */
#[ORM\Table(name: 'sy_CalculationState')]
#[ORM\Entity(repositoryClass: CalculationStateRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_calculation_state_code', columns: ['code'])]
#[UniqueEntity(fields: 'code', message: 'state.unique_code')]
class CalculationState extends AbstractCodeEntity
{
    /** The default color (black). */
    public const string DEFAULT_COLOR = '#000000';

    /**
     * The calculations.
     *
     * @var Collection<int, Calculation>
     */
    #[ORM\OneToMany(targetEntity: Calculation::class, mappedBy: 'state', fetch: self::EXTRA_LAZY)]
    private Collection $calculations;

    /** The color used in the user interface (UI). */
    #[Assert\CssColor]
    #[Assert\NotBlank]
    #[Assert\Length(max: 10)]
    #[ORM\Column(length: 10, options: ['default' => self::DEFAULT_COLOR])]
    private string $color = self::DEFAULT_COLOR;

    /** The editable state. */
    #[ORM\Column(options: ['default' => true])]
    private bool $editable = true;

    public function __construct()
    {
        $this->calculations = new ArrayCollection();
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
     * Get color as a hexadecimal value.
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * Gets this color as a PdfRgbColor object.
     */
    public function getRgbColor(): ?PdfRgbColor
    {
        return PdfRgbColor::create($this->color);
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
     * Set color as a hexadecimal value.
     */
    public function setColor(string $color): self
    {
        $this->color = $color;

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
