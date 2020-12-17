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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a digi print item type.
 *
 * @ORM\Table(name="sy_DigiPrintItem")
 * @ORM\Entity(repositoryClass="App\Repository\DigiPrintItemRepository")
 */
class DigiPrintItem extends AbstractEntity
{
    use MathTrait;

    /**
     * The backlit type.
     *
     * @var int
     */
    public const TYPE_BACKLIT = 1;

    /**
     * The price type.
     */
    public const TYPE_PRICE = 0;

    /**
     * The replicating type.
     */
    public const TYPE_REPLICATING = 2;

    /**
     * The allowed types.
     */
    public const TYPES = [
        self::TYPE_PRICE,
        self::TYPE_BACKLIT,
        self::TYPE_REPLICATING,
    ];

    /**
     * @ORM\Column(type="float", scale=2)
     * @Assert\Type(type="float")
     * @Assert\GreaterThanOrEqual(0)
     *
     * @var float
     */
    private $amount;

    /**
     * @ORM\ManyToOne(targetEntity=DigiPrint::class, inversedBy="items")
     * @ORM\JoinColumn(name="digi_print_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     *
     * @var DigiPrint
     */
    private $digiPrint;

    /**
     * @ORM\Column(type="smallint")
     * @Assert\Type(type="int")
     * @Assert\GreaterThanOrEqual(0)
     * @Assert\GreaterThan(propertyPath="minimum", message="digiprint.maximum_geather_minimum")
     *
     * @var int
     */
    private $maximum;

    /**
     * @ORM\Column(type="smallint")
     * @Assert\Type(type="int")
     * @Assert\GreaterThanOrEqual(0)
     *
     * @var int
     */
    private $minimum;

    /**
     * @ORM\Column(type="smallint")
     * @Assert\Type(type="int")
     * @Assert\Choice(DigiPrintItem::TYPES, message="digiprint.type")
     *
     * @var int
     */
    private $type;

    /**
     * Constructor.
     *
     * @param int $type the item type, one of this <code>TYPE_*</code> constants
     *
     * @throws \InvalidArgumentException if the type is not one of this defined constants
     */
    public function __construct(int $type = self::TYPE_PRICE)
    {
        $this->minimum = 0;
        $this->maximum = 1;
        $this->amount = 0.0;
        $this->setType($type);
    }

    /**
     * Checks if the given quantity is between this minimum (inclusive) and this maximum (exlcusive).
     *
     * @param int $quantity the quantity to verify
     *
     * @return bool true if within this range
     */
    public function contains(int $quantity): bool
    {
        return $quantity >= $this->minimum && $quantity <= $this->maximum;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Gets the total amount for the given quantity.
     *
     * @param int $quantity the quantity to get amount for
     *
     * @return float the amount
     */
    public function getAmountQuantity(int $quantity): float
    {
        return $this->amount * $quantity;
    }

    public function getDigiPrint(): ?DigiPrint
    {
        return $this->digiPrint;
    }

    public function getMaximum(): int
    {
        return $this->maximum;
    }

    public function getMinimum(): int
    {
        return $this->minimum;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $this->round($amount);

        return $this;
    }

    public function setDigiPrint(?DigiPrint $digiPrint): self
    {
        $this->digiPrint = $digiPrint;

        return $this;
    }

    public function setMaximum(int $maximum): self
    {
        $this->maximum = $maximum;

        return $this;
    }

    public function setMinimum(int $minimum): self
    {
        $this->minimum = $minimum;

        return $this;
    }

    /**
     * Sets the item type.
     *
     * @param int $type one of this <code>TYPE_*</code> constants
     *
     * @throws \InvalidArgumentException if the type is not one of this defined constants
     */
    public function setType(int $type): self
    {
        if (!\in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException("The item type '$type' is invalid.");
        }

        $this->type = $type;

        return $this;
    }
}
