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

namespace App\Tests\Spreadsheet;

use App\Controller\AbstractController;
use App\Spreadsheet\CalculationsBelowDocument;
use App\Utils\DateUtils;
use PHPUnit\Framework\TestCase;

final class CalculationsBelowDocumentTest extends TestCase
{
    public function testRender(): void
    {
        $controller = self::createStub(AbstractController::class);
        $calculation = [
            'id' => 1,
            'date' => DateUtils::createDate('2019-01-01'),
            'customer' => 'Customer 1',
            'description' => 'Description 1',
            'itemsTotal' => 1300.0,
            'overallTotal' => 1350.0,
            'code' => 'State 1',
            'editable' => true,
        ];
        $document = new CalculationsBelowDocument($controller, [$calculation]);
        $actual = $document->render();
        self::assertTrue($actual);
    }
}
