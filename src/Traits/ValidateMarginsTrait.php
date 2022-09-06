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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Trait to validate margins within a callback.
 */
trait ValidateMarginsTrait
{
    /**
     * Get margins.
     *
     * @pslam-template T extends MarginInterface
     *
     * @pslam-return Collection<int, T>
     */
    abstract public function getMargins(): Collection;

    #[Assert\Callback]
    public function validateMargins(ExecutionContextInterface $context): void
    {
        /** @var ArrayCollection<int, MarginInterface> $margins */
        $margins = $this->getMargins();

        // margins?
        if (\count($margins) < 2) {
            return;
        }

        // sort
        $criteria = Criteria::create()
            ->orderBy(['minimum' => Criteria::ASC]);
        $margins = $margins->matching($criteria);

        $lastMax = null;
        foreach ($margins as $key => $margin) {
            $min = $margin->getMinimum();
            $max = $margin->getMaximum();

            // the maximum is smaller than or equal to the minimum
            if ($max <= $min) {
                $context->buildViolation('margin.maximum_greater_minimum')
                    ->atPath("margins[$key].maximum")
                    ->addViolation();
                break;
            }

            // first time
            if (null === $lastMax) {
                $lastMax = $max;
                continue;
            }

            // the minimum is overlapping the previous margin
            if ($min < $lastMax) {
                $context->buildViolation('margin.minimum_overlap')
                    ->atPath("margins[$key].minimum")
                    ->addViolation();
                break;
            }

            // the maximum is overlapping the previous margin
            if ($max < $lastMax) {
                $context->buildViolation('margin.maximum_overlap')
                    ->atPath("margins[$key].maximum")
                    ->addViolation();
                break;
            }

            // the minimum is not equal to the previous maximum
            if ($min !== $lastMax) {
                $context->buildViolation('margin.minimum_discontinued')
                    ->atPath("margins[$key].minimum")
                    ->addViolation();
                break;
            }

            // copy
            $lastMax = $max;
        }
    }
}
