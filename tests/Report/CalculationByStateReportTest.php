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
use App\Model\CalculationsState;
use App\Model\CalculationsStateItem;
use App\Report\CalculationByStateReport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CalculationByStateReportTest extends TestCase
{
    public function testRender(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $controller->method('getMinMargin')
            ->willReturn(1.1);
        $generator = self::createStub(UrlGeneratorInterface::class);
        $items = [
            new CalculationsStateItem(
                id: 1,
                code: 'Code1',
                editable: true,
                color: 'red',
                count: 1,
                items: 1.0,
                total: 2.0
            ),
            new CalculationsStateItem(
                id: 2,
                code: 'Code2',
                editable: false,
                color: 'black',
                count: 1,
                items: 1.0,
                total: 2.0
            ),
        ];
        $state = new CalculationsState($items);
        $report = new CalculationByStateReport($controller, $state, $generator);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
