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
use App\Model\CalculationsMonth;
use App\Model\CalculationsMonthItem;
use App\Report\CalculationByMonthReport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalculationByMonthReportTest extends TestCase
{
    public function testNewPage(): void
    {
        $items = [];
        for ($i = 0; $i <= 15; ++$i) {
            $items[] = new CalculationsMonthItem(
                count: 5,
                items: 15.0,
                total: 35.0,
                year: 2024,
                month: 3
            );
        }
        $report = $this->createReport($items);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRender(): void
    {
        $items = [
            new CalculationsMonthItem(
                count: 1,
                items: 10.0,
                total: 20.0,
                year: 2024,
                month: 1
            ),
            new CalculationsMonthItem(
                count: 10,
                items: 20.0,
                total: 40.0,
                year: 2024,
                month: 2
            ),
            new CalculationsMonthItem(
                count: 5,
                items: 15.0,
                total: 35.0,
                year: 2024,
                month: 3
            ),
            new CalculationsMonthItem(
                count: 5,
                items: 15.0,
                total: 35.0,
                year: 2024,
                month: 3
            ),
        ];
        $report = $this->createReport($items);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @param CalculationsMonthItem[] $items
     */
    private function createReport(array $items): CalculationByMonthReport
    {
        $controller = $this->createMock(AbstractController::class);
        $controller->method('getMinMargin')
            ->willReturn(1.1);
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $month = new CalculationsMonth($items);

        return new CalculationByMonthReport($controller, $month, $generator);
    }
}
