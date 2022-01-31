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

use App\Util\FormatUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abstract margin used for global margins and group's margins.
 *
 * @author Laurent Muller
 *
 * @ORM\MappedSuperclass
 */
abstract class AbstractMargin extends AbstractEntity
{
    /**
     * The margin in percent (%).
     *
     * @ORM\Column(type="float", scale=2, options={"default" = 0})
     * @Assert\Type(type="float")
     * @Assert\GreaterThanOrEqual(0)
     */
    protected float $margin = 0.0;

    /**
     * The maximum amount (exclusive) to apply within this margin.
     *
     * @ORM\Column(type="float", scale=2, options={"default" = 0})
     * @Assert\Type(type="float")
     * @Assert\GreaterThanOrEqual(0)
     * @Assert\GreaterThan(propertyPath="minimum", message="abstract_margin.maximum_geather_minimum")
     */
    protected float $maximum = 0.0;

    /**
     * The minimum amount (inclusive) to apply within this margin.
     *
     * @ORM\Column(type="float", scale=2, options={"default" = 0})
     * @Assert\Type(type="float")
     * @Assert\GreaterThanOrEqual(0)
     */
    protected float $minimum = 0.0;

    /**
     * Checks if the given amount is between this minimum (inclusive) and this maximum (exlcusive).
     *
     * @param float $amount the amount to verify
     *
     * @return bool true if within this range
     */
    public function contains(float $amount): bool
    {
        return $amount >= $this->minimum && $amount < $this->maximum;
    }

    /**
     * {@inheritdoc}
     *
     * @see \App\Entity\AbstractEntity::getDisplay()
     */
    public function getDisplay(): string
    {
        return FormatUtils::formatAmount($this->getMinimum()) . ' - ' . FormatUtils::formatAmount($this->getMaximum());
    }

    /**
     * Get margin in percent.
     */
    public function getMargin(): float
    {
        return $this->margin;
    }

    /**
     * Gets the margin amount for the given value.
     */
    public function getMarginAmount(float $amount): float
    {
        return $this->margin * $amount;
    }

    /**
     * Get maximum.
     */
    public function getMaximum(): float
    {
        return $this->maximum;
    }

    /**
     * Get minimum.
     */
    public function getMinimum(): float
    {
        return $this->minimum;
    }

    /**
     * Set margin in percent.
     */
    public function setMargin(float $margin): self
    {
        $this->margin = $this->round($margin);

        return $this;
    }

    /**
     * Set maximum.
     */
    public function setMaximum(float $maximum): self
    {
        $this->maximum = $this->round($maximum);

        return $this;
    }

    /**
     * Set minimum.
     */
    public function setMinimum(float $minimum): self
    {
        $this->minimum = $this->round($minimum);

        return $this;
    }

    /**
     * Set values.
     */
    public function setValues(float $minimum, float $maximum, float $margin): self
    {
        return $this->setMinimum($minimum)
            ->setMaximum($maximum)
            ->setMargin($margin);
    }
}
