<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Entity;

use App\Traits\MathTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a line of calculation.
 *
 * @ORM\Entity(repositoryClass="App\Repository\CalculationLineRepository")
 * @ORM\Table(name="sy_CalculationLine")
 */
class CalculationLine extends AbstractEntity
{
    use MathTrait;

    /**
     * The category line type.
     */
    public const TYPE_CATEGORY = 1;

    /**
     * The group line type.
     */
    public const TYPE_GROUP = 0;

    /**
     * The item line type.
     */
    public const TYPE_ITEM = 2;

    /**
     * The allowed types.
     *
     * @var int[]
     */
    public const TYPES = [
        self::TYPE_GROUP,
        self::TYPE_CATEGORY,
        self::TYPE_ITEM,
    ];

    /**
     * @ORM\Column(type="float", scale=2, options={"default": 0})
     *
     * @var float
     */
    private $amount;

    /**
     * @ORM\ManyToOne(targetEntity="Calculation", inversedBy="calculationLines")
     * @ORM\JoinColumn(name="calculation_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     *
     * @var ?Calculation
     */
    private $calculation;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     *
     * @var string
     */
    private $description;

    /**
     * The children lines.
     *
     * @ORM\OneToMany(targetEntity="CalculationLine", mappedBy="parent", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"code": "ASC"})
     * @Assert\Valid
     *
     * @var Collection|CalculationLine[]
     */
    private $lines;

    /**
     * @ORM\Column(type="float", scale=2, options={"default": 1})
     *
     * @var float
     */
    private $margin;

    /**
     * The parent line.
     *
     * @ORM\ManyToOne(targetEntity="CalculationLine", inversedBy="lines")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     *
     * @var ?CalculationLine
     */
    private $parent;

    /**
     * @ORM\Column(type="float", scale=2, options={"default": 1})
     *
     * @var float
     */
    private $quantity;

    /**
     * @ORM\Column(type="smallint")
     * @Assert\Choice(CalculationLine::TYPES, message="The line type is invalid.")
     *
     * @var int
     */
    private $type;

    /**
     * Constructor.
     *
     * @param int $type the line type, one of this <code>TYPE_*</code> constants
     *
     * @throws \InvalidArgumentException if the type is not one of this defined constants
     */
    public function __construct(int $type = self::TYPE_ITEM)
    {
        $this->amount = 0.0;
        $this->margin = 1.0;
        $this->quantity = 1.0;
        $this->setType($type);
        $this->lines = new ArrayCollection();
    }

    public function addLine(self $line): self
    {
        if (!$this->contains($line)) {
            $this->lines->add($line);
            $line->setParent($this);
        }

        return $this;
    }

    /**
     * Checks whether the given line is contained within this collection of lines.
     *
     * @param CalculationLine $line the line to search for
     *
     * @return bool true if this collection contains the line, false otherwise
     */
    public function contains(self $line): bool
    {
        return $this->lines->contains($line);
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function getCalculation(): ?Calculation
    {
        return $this->calculation;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the children lines.
     *
     * @return Collection|CalculationLine[]
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function getMargin(): ?float
    {
        return $this->margin;
    }

    public function getMarginAmount(): float
    {
        return $this->amount * $this->quantity * ($this->margin - 1);
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getParentDescription(): ?string
    {
        return $this->parent ? $this->parent->description : null;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    /**
     * Gets the total.
     */
    public function getTotal(): float
    {
        return $this->amount * $this->quantity * $this->margin;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function removeLine(self $line): self
    {
        if ($this->lines->removeElement($line)) {
            if ($line->getParent() === $this) {
                $line->setParent(null);
            }
        }

        return $this;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $this->round($amount);

        return $this;
    }

    public function setCalculation(?Calculation $calculation): self
    {
        $this->calculation = $calculation;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function setMargin(float $margin): self
    {
        $this->margin = $this->round($margin);

        return $this;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function setQuantity(float $quantity): self
    {
        $this->quantity = $this->round($quantity);

        return $this;
    }

    /**
     * Sets the line type.
     *
     * @param int $type one of this <code>TYPE_*</code> constants
     *
     * @throws \InvalidArgumentException if the type is not one of this defined constants
     */
    public function setType(int $type): self
    {
        if (!\in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException("The line type '$type' is invalid.");
        }

        $this->type = $type;

        return $this;
    }
}
