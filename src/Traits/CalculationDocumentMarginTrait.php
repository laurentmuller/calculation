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

/**
 * Trait to get the overall margin format.
 *
 * @psalm-require-extends \App\Spreadsheet\AbstractDocument
 */
trait CalculationDocumentMarginTrait
{
    /**
     * Gets the overall margin format.
     */
    protected function getMarginFormat(): string
    {
        $minMargin = $this->controller->getMinMargin();
        $format = $this->getActiveSheet()->getPercentFormat();

        return "[Black][=0]$format;[Red][<$minMargin]$format;$format";
    }
}
