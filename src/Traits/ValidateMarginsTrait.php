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

namespace App\Traits;

use App\Interfaces\MarginInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Trait to validate margins within a callback.
 *
 * @template TMarginKey of array-key
 * @template TMarginValue of MarginInterface
 */
trait ValidateMarginsTrait
{
    /**
     * Get margins.
     *
     * @phpstan-return Collection<TMarginKey, TMarginValue>
     */
    abstract public function getMargins(): Collection;

    #[Assert\Callback]
    public function validateMargins(ExecutionContextInterface $context): void
    {
        $margins = $this->getMargins();
        if ($margins->isEmpty()) {
            return;
        }

        $values = $margins->toArray();
        if (\count($values) > 1) {
            \uasort($values, static fn (MarginInterface $a, MarginInterface $b): int => $a->getMinimum() <=> $b->getMinimum());
        }

        $lastMax = null;
        foreach ($values as $key => $margin) {
            $min = $margin->getMinimum();
            $max = $margin->getMaximum();
            if ($max <= $min) {
                $context->buildViolation('margin.maximum_greater_minimum')
                    ->atPath(\sprintf('margins[%s].maximum', $key))
                    ->addViolation();
                break;
            }
            if (null === $lastMax) {
                $lastMax = $max;
                continue;
            }
            if ($min < $lastMax) {
                $context->buildViolation('margin.minimum_overlap')
                    ->atPath(\sprintf('margins[%s].minimum', $key))
                    ->addViolation();
                break;
            }
            if ($min !== $lastMax) {
                $context->buildViolation('margin.minimum_discontinued')
                    ->atPath(\sprintf('margins[%s].minimum', $key))
                    ->addViolation();
                break;
            }
            $lastMax = $max;
        }
    }
}
