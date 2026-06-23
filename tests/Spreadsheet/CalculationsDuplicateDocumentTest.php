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

use App\Interfaces\DocumentHelperInterface;
use App\Spreadsheet\CalculationsDuplicateDocument;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;

final class CalculationsDuplicateDocumentTest extends TestCase
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
        $helper = self::createStub(DocumentHelperInterface::class);
        $document = new CalculationsDuplicateDocument($helper, [$data]);
        $actual = $document->render();
        self::assertTrue($actual);
    }
}
