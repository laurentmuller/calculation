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
use App\Spreadsheet\CalculationsDuplicateDocument;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;

class CalculationsDuplicateDocumentTest extends TestCase
{
    public function testRender(): void
    {
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
        $controller = $this->createMock(AbstractController::class);
        $document = new CalculationsDuplicateDocument($controller, [$data]);
        $actual = $document->render();
        self::assertTrue($actual);
    }
}
