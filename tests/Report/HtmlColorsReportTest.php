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

namespace App\Tests\Report;

use App\Interfaces\DocumentHelperInterface;
use App\Report\HtmlColorsReport;
use PHPUnit\Framework\TestCase;

final class HtmlColorsReportTest extends TestCase
{
    public function testReport(): void
    {
        $helper = self::createStub(DocumentHelperInterface::class);
        $report = new HtmlColorsReport($helper);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
