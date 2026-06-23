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

use App\Entity\GlobalMargin;
use App\Interfaces\DocumentHelperInterface;
use App\Report\GlobalMarginsReport;
use PHPUnit\Framework\TestCase;

final class GlobalMarginsReportTest extends TestCase
{
    public function testRender(): void
    {
        $helper = self::createStub(DocumentHelperInterface::class);
        $margin = new GlobalMargin();
        $margin->setMinimum(0.0)
            ->setMaximum(100.0)
            ->setMargin(1.1);
        $report = new GlobalMarginsReport($helper, [$margin]);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
