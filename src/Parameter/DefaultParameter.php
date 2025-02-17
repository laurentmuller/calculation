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
use App\Entity\Calculation;
use App\Traits\MathTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Default parameter.
 */
class DefaultParameter implements ParameterInterface
{
    use MathTrait;

    #[Parameter('default_category')]
    private ?int $categoryId = null;

    #[Assert\GreaterThanOrEqual(0.0)]
    #[Parameter('minimum_margin', 1.1)]
    private float $minMargin = 1.1;

    #[Parameter('default_state')]
    private ?int $stateId = null;

    #[\Override]
    public static function getCacheKey(): string
    {
        return 'parameter_default_value';
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getMinMargin(): float
    {
        return $this->minMargin;
    }

    public function getStateId(): ?int
    {
        return $this->stateId;
    }

    public function isMarginBelow(Calculation|float $value): bool
    {
        if ($value instanceof Calculation) {
            return $value->isMarginBelow($this->minMargin);
        }

        return !$this->isFloatZero($value) && $value < $this->minMargin;
    }

    public function setCategoryId(?int $categoryId): self
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function setMinMargin(float $minMargin): self
    {
        $this->minMargin = $minMargin;

        return $this;
    }

    public function setStateId(?int $stateId): self
    {
        $this->stateId = $stateId;

        return $this;
    }
}
