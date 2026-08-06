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
use App\Tests\Fixture\FixtureReport;
use App\Tests\TranslatorStubTrait;
use fpdf\PdfDocument;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PdfCellTranslatorTraitTest extends TestCase
{
    use TranslatorStubTrait;

    private Stub&TranslatorInterface $translator;

    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createStubTranslator();
    }

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

            #[\Override]
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

    private function createReport(): FixtureReport
    {
        $controller = self::createStub(AbstractController::class);
        $controller->method('getTranslator')
            ->willReturn($this->translator);

        return new FixtureReport($controller);
    }
}
