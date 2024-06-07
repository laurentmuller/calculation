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
use App\Report\CalculationByStateReport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[CoversClass(CalculationByStateReport::class)]
class CalculationByStateReportTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testRender(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $data1 = [
            'id' => 1,
            'code' => 'Code1',
            'editable' => true,
            'color' => 'red',
            'count' => 1,
            'items' => 1.0,
            'total' => 2.0,
            'margin_percent' => 1.0,
            'margin_amount' => 1.0,
            'percent_calculation' => 1.0,
            'percent_amount' => 1.0,
        ];
        $data2 = [
            'id' => 1,
            'code' => 'Code1',
            'editable' => true,
            'color' => 'black',
            'count' => 1,
            'items' => 1.0,
            'total' => 2.0,
            'margin_percent' => 1.0,
            'margin_amount' => 1.0,
            'percent_calculation' => 1.0,
            'percent_amount' => 1.0,
        ];
        $entities = [$data1, $data2];
        $report = new CalculationByStateReport($controller, $entities, $generator);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
