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

namespace App\Tests\Pdf\Traits;

use App\Controller\AbstractController;
use App\Pdf\PdfColumn;
use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfCellTranslatorTrait;
use App\Tests\Fixture\TestReport;
use App\Tests\TranslatorMockTrait;
use fpdf\PdfDocument;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class PdfCellTranslatorTraitTest extends TestCase
{
    use TranslatorMockTrait;

    private MockObject&TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->createMockTranslator();
    }

    /**
     * @throws Exception
     */
    public function testRender(): void
    {
        $document = $this->createReport();
        $table = new class($document, $this->translator) extends PdfTable {
            use PdfCellTranslatorTrait;

            public function __construct(PdfDocument $parent, private readonly TranslatorInterface $translator)
            {
                parent::__construct($parent);
            }

            public function render(): bool
            {
                $this->addColumn(PdfColumn::left(width: 10.0));
                $this->startRow();
                $this->addCellTrans('id');
                $this->endRow();

                return true;
            }

            public function getTranslator(): TranslatorInterface
            {
                return $this->translator;
            }
        };
        $document->resetStyle()
            ->addPage();
        $actual = $table->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    private function createReport(): TestReport
    {
        $controller = $this->createMock(AbstractController::class);
        $controller->method('getTranslator')
            ->willReturn($this->translator);

        return new TestReport($controller);
    }
}
