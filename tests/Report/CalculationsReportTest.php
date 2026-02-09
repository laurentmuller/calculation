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

use App\Controller\AbstractController;
use App\Entity\Calculation;
use App\Report\CalculationsReport;
use PHPUnit\Framework\TestCase;

final class CalculationsReportTest extends TestCase
{
    public function testRender(): void
    {
        $controller = self::createStub(AbstractController::class);

        $calculation1 = $this->createMock(Calculation::class);
        $calculation1->method('isMarginBelow')
            ->willReturn(true);

        $calculation2 = $this->createMock(Calculation::class);
        $calculation2->method('isMarginBelow')
            ->willReturn(false);
        $calculation2->method('isEditable')
            ->willReturn(true);
        $calculation2->method('getStateCode')
            ->willReturn('state1');

        $calculation3 = $this->createMock(Calculation::class);
        $calculation3->method('isMarginBelow')
            ->willReturn(true);
        $calculation3->method('isEditable')
            ->willReturn(true);
        $calculation3->method('getStateCode')
            ->willReturn('state1');

        $report = new CalculationsReport($controller, [$calculation1, $calculation2, $calculation3]);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
