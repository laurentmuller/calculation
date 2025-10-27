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
use App\Report\CalculationsBelowReport;
use PHPUnit\Framework\TestCase;

final class CalculationsBelowReportTest extends TestCase
{
    public function testRender(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $calculation = new Calculation();

        $report = new CalculationsBelowReport($controller, [$calculation]);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
