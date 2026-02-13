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

namespace App\Tests\Traits;

use App\Controller\AbstractController;
use App\Spreadsheet\AbstractDocument;
use App\Traits\CalculationDocumentMarginTrait;
use PHPUnit\Framework\TestCase;

final class CalculationDocumentMarginTraitTest extends TestCase
{
    public function testMarginFormat(): void
    {
        $controller = self::createStub(AbstractController::class);
        $document = new class($controller) extends AbstractDocument {
            use CalculationDocumentMarginTrait;

            public function getFormat(): string
            {
                return $this->getMarginFormat($this->getActiveSheet(), 1.1);
            }

            #[\Override]
            public function render(): bool
            {
                return true;
            }
        };

        $expected = '[Black][=0]0%;[Red][<1.1]0%;0%';
        $actual = $document->getFormat();
        self::assertSame($expected, $actual);
    }
}
