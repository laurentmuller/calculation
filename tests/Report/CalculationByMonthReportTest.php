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
use App\Report\CalculationByMonthReport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @psalm-import-type CalculationByMonthType from \App\Repository\CalculationRepository
 */
class CalculationByMonthReportTest extends TestCase
{
    public function testNewPage(): void
    {
        $entities = [];
        for ($i = 0; $i <= 15; ++$i) {
            $entities[] = [
                'count' => 5,
                'items' => 15.0,
                'total' => 35.0,
                'year' => 2024,
                'month' => 3,
                'margin_percent' => 0.15,
                'margin_amount' => 7.0,
                'date' => new \DateTime(),
            ];
        }
        $report = $this->createReport($entities);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRender(): void
    {
        $data1 = [
            'count' => 1,
            'items' => 10.0,
            'total' => 20.0,
            'year' => 2024,
            'month' => 1,
            'margin_percent' => 0.1,
            'margin_amount' => 5.0,
            'date' => new \DateTime(),
        ];
        $data2 = [
            'count' => 10,
            'items' => 20.0,
            'total' => 40.0,
            'year' => 2024,
            'month' => 2,
            'margin_percent' => 0.2,
            'margin_amount' => 10.0,
            'date' => new \DateTime(),
        ];
        $data3 = [
            'count' => 5,
            'items' => 15.0,
            'total' => 35.0,
            'year' => 2024,
            'month' => 3,
            'margin_percent' => 0.15,
            'margin_amount' => 7.0,
            'date' => new \DateTime(),
        ];
        $data4 = [
            'count' => 5,
            'items' => 15.0,
            'total' => 35.0,
            'year' => 2024,
            'month' => 3,
            'margin_percent' => 0.15,
            'margin_amount' => 7.0,
            'date' => new \DateTime(),
        ];
        $entities = [$data1, $data2, $data3, $data4];
        $report = $this->createReport($entities);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @psalm-param CalculationByMonthType[] $entities
     */
    private function createReport(array $entities): CalculationByMonthReport
    {
        $controller = $this->createMock(AbstractController::class);
        $controller->method('getMinMargin')
            ->willReturn(1.1);
        $generator = $this->createMock(UrlGeneratorInterface::class);

        return new CalculationByMonthReport($controller, $entities, $generator);
    }
}
