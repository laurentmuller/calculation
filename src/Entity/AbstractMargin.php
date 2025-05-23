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

use App\Interfaces\MarginInterface;
use App\Types\FixedFloatType;
use App\Utils\FormatUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abstract margin used for global margins and group's margins.
 */
#[ORM\MappedSuperclass]
abstract class AbstractMargin extends AbstractEntity implements MarginInterface
{
    /**
     * The margin in percent (%) to use when an amount is within this range.
     */
    #[Assert\GreaterThanOrEqual(1.0)]
    #[ORM\Column(type: FixedFloatType::NAME)]
    private float $margin = 1.0;

    /**
     * The maximum amount (exclusive) to apply within this margin.
     */
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\GreaterThan(propertyPath: 'minimum', message: 'margin.maximum_greater_minimum')]
    #[ORM\Column(type: FixedFloatType::NAME)]
    private float $maximum = 0.0;

    /**
     * The minimum amount (inclusive) to apply within this margin.
     */
    #[Assert\GreaterThanOrEqual(0)]
    #[ORM\Column(type: FixedFloatType::NAME)]
    private float $minimum = 0.0;

    #[\Override]
    public function contains(float $value): bool
    {
        return $value >= $this->minimum && $value < $this->maximum;
    }

    /**
     * Gets the difference between the maximum and the minimum values.
     */
    public function getDelta(): float
    {
        return $this->maximum - $this->minimum;
    }

    #[\Override]
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

    #[\Override]
    public function getMaximum(): float
    {
        return $this->maximum;
    }

    #[\Override]
    public function getMinimum(): float
    {
        return $this->minimum;
    }

    /**
     * Set the margin in percent.
     */
    public function setMargin(float $margin): static
    {
        $this->margin = $this->round($margin);

        return $this;
    }

    /**
     * Set the maximum.
     */
    public function setMaximum(float $maximum): static
    {
        $this->maximum = $this->round($maximum);

        return $this;
    }

    /**
     * Set the minimum.
     */
    public function setMinimum(float $minimum): static
    {
        $this->minimum = $this->round($minimum);

        return $this;
    }
}
