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

namespace App\Tests\Fixture;

use App\Pdf\Interfaces\PdfChartInterface;
use App\Pdf\Traits\PdfChartLegendTrait;
use App\Report\AbstractReport;

class FixturePdfChartLegendReport extends AbstractReport implements PdfChartInterface
{
    use PdfChartLegendTrait;

    #[\Override]
    public function render(): bool
    {
        return true;
    }
}
