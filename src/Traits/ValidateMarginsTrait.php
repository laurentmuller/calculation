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

use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Trait to validate margins within a callback.
 */
trait ValidateMarginsTrait
{
    #[Assert\Callback]
    public function validateMargins(ExecutionContextInterface $context): void
    {
        /** @var \Doctrine\Common\Collections\ArrayCollection<int, \App\Interfaces\MarginInterface> $margins */
        $margins = $this->getMargins();
        if (\count($margins) < 2) {
            return;
        }

        // sort
        $criteria = Criteria::create()
            ->orderBy(['minimum' => Criteria::ASC]);
        $margins = $margins->matching($criteria);

        $lastMin = null;
        $lastMax = null;
        foreach ($margins as $key => $margin) {
            $min = $margin->getMinimum();
            $max = $margin->getMaximum();

            if (null === $lastMin) {
                // first time
                $lastMin = $min;
                $lastMax = $max;
            } elseif ($min <= $lastMin) {
                // the minimum is smaller than the previous maximum
                $context->buildViolation('margin.minimum_overlap')
                    ->atPath("margins[$key].minimum")
                    ->addViolation();
                break;
            } elseif ($min >= $lastMin && $min < $lastMax) {
                // the minimum is overlapping the previous margin
                $context->buildViolation('margin.minimum_overlap')
                    ->atPath("margins[$key].minimum")
                    ->addViolation();
                break;
            } elseif ($max > $lastMin && $max < $lastMax) {
                // the maximum is overlapping the previous margin
                $context->buildViolation('margin.maximum_overlap')
                    ->atPath("margins[$key].maximum")
                    ->addViolation();
                break;
            } elseif ($min !== $lastMax) {
                // the minimum is not equal to the previous maximum
                $context->buildViolation('margin.minimum_discontinued')
                    ->atPath("margins[$key].minimum")
                    ->addViolation();
                break;
            } else {
                // copy
                $lastMin = $min;
                $lastMax = $max;
            }
        }
    }
}
