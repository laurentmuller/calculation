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
use App\Entity\CalculationState;
use App\Spreadsheet\CalculationStatesDocument;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class CalculationStatesDocumentTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testRender(): void
    {
        $state1 = new CalculationState();
        $state1->setCode('Code1');

        $state2 = new CalculationState();
        $state2->setCode('Code2')
            ->setColor('');

        $controller = $this->createMock(AbstractController::class);
        $document = new CalculationStatesDocument($controller, [$state1, $state2]);
        $actual = $document->render();
        self::assertTrue($actual);
    }
}
