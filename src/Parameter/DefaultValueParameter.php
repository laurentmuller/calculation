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

namespace App\Parameter;

use App\Attribute\Parameter;
use App\Entity\CalculationState;
use App\Entity\Category;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Default value parameter.
 */
class DefaultValueParameter implements ParameterInterface
{
    #[Parameter('default_category')]
    private ?Category $category = null;

    #[Assert\GreaterThanOrEqual(0.0)]
    #[Parameter('minimum_margin', 1.1)]
    private float $minMargin = 1.1;

    #[Parameter('default_state')]
    private ?CalculationState $state = null;

    public static function getCacheKey(): string
    {
        return 'parameter_default_value';
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function getMinMargin(): float
    {
        return $this->minMargin;
    }

    public function getState(): ?CalculationState
    {
        return $this->state;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function setMinMargin(float $minMargin): self
    {
        $this->minMargin = $minMargin;

        return $this;
    }

    public function setState(?CalculationState $state): self
    {
        $this->state = $state;

        return $this;
    }
}
