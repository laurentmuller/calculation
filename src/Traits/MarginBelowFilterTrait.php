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

use App\Doctrine\CalculationBelowFilter;
use App\Repository\AbstractRepository;
use Doctrine\DBAL\Types\Types;

/**
 * @phpstan-require-extends AbstractRepository
 */
trait MarginBelowFilterTrait
{
    /**
     * Enable calculation margin below filter.
     */
    public function enableMarginBelowFilter(float $minMargin): static
    {
        $this->getEntityManager()
            ->getFilters()
            ->enable(CalculationBelowFilter::NAME)
            ->setParameter(CalculationBelowFilter::MARGIN_PARAMETER, $minMargin, Types::FLOAT);

        return $this;
    }
}
