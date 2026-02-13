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
use App\Report\CalculationsReport;
use App\Utils\DateUtils;
use PHPUnit\Framework\TestCase;

final class CalculationsReportTest extends TestCase
{
    public function testRender(): void
    {
        $controller = self::createStub(AbstractController::class);
        $controller->method('getMinMargin')
            ->willReturn(1.1);
        $calculation1 = [
            'id' => 1,
            'date' => DateUtils::createDate('2019-01-01'),
            'customer' => 'Customer 1',
            'description' => 'Description 1',
            'itemsTotal' => 1300.0,
            'overallTotal' => 1350.0,
            'code' => 'State 1',
            'editable' => true,
        ];
        $calculation2 = [
            'id' => 2,
            'date' => DateUtils::createDate('2019-01-03'),
            'customer' => 'Customer 2',
            'description' => 'Description 2',
            'itemsTotal' => 8.0,
            'overallTotal' => 30.0,
            'code' => 'State 1',
            'editable' => true,
        ];
        $report = new CalculationsReport($controller, [$calculation1, $calculation2]);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
