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

use App\Doctrine\FixedFloatType;
use App\Interfaces\MarginInterface;
use App\Utils\FormatUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abstract margin used for global margins and group's margins.
 */
#[ORM\MappedSuperclass]
abstract class AbstractMargin extends AbstractEntity implements MarginInterface
{
    /** The margin in percent (1.0 = 100%) to use when an amount is within this range. */
    #[Assert\GreaterThanOrEqual(1.0)]
    #[ORM\Column(type: FixedFloatType::NAME)]
    public float $margin = 1.0 {
        set => $this->round($value);
    }

    /** The maximum amount (exclusive) to apply within this margin. */
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\GreaterThan(propertyPath: 'minimum', message: 'margin.maximum_greater_minimum')]
    #[ORM\Column(type: FixedFloatType::NAME)]
    public float $maximum = 0.0 {
        get => $this->maximum;
        set => $this->round($value);
    }

    /** The minimum amount (inclusive) to apply within this margin. */
    #[Assert\GreaterThanOrEqual(0)]
    #[ORM\Column(type: FixedFloatType::NAME)]
    public float $minimum = 0.0 {
        get => $this->minimum;
        set => $this->round($value);
    }

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
        return \sprintf(
            '%s - %s',
            FormatUtils::formatAmount($this->minimum),
            FormatUtils::formatAmount($this->maximum)
        );
    }

    /**
     * Gets the margin amount for the given value.
     */
    public function getMarginAmount(float $amount): float
    {
        return $this->margin * $amount;
    }

    /**
     * Set the margin in percent (1.0 = 100%).
     */
    public function withMargin(float $margin): static
    {
        $this->margin = $margin;

        return $this;
    }

    /**
     * Set the maximum.
     */
    public function withMaximum(float $maximum): static
    {
        $this->maximum = $maximum;

        return $this;
    }

    /**
     * Set the minimum.
     */
    public function withMinimum(float $minimum): static
    {
        $this->minimum = $minimum;

        return $this;
    }
}
