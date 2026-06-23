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
use App\Report\CalculationsDuplicateReport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;

final class CalculationsDuplicateReportTest extends TestCase
{
    public function testRender(): void
    {
        $helper = self::createStub(DocumentHelperInterface::class);
        $data = [
            'id' => 1,
            'date' => new DatePoint(),
            'stateCode' => 'stateCode',
            'customer' => 'customer',
            'description' => 'description',
            'items' => [
                [
                    'description' => 'description',
                    'quantity' => 1.0,
                    'price' => 1.0,
                    'count' => 2,
                ],
            ],
        ];
        $report = new CalculationsDuplicateReport($helper, [$data]);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
