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

use App\Pdf\PdfColumn;
use App\Pdf\PdfDocument;
use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfCellTranslatorTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(PdfCellTranslatorTrait::class)]
class PdfCellTranslatorTraitTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testRender(): void
    {
        $document = new PdfDocument();
        $translator = $this->createMock(TranslatorInterface::class);

        $table = new class($document, $translator) extends PdfTable {
            use PdfCellTranslatorTrait;

            public function __construct(PdfDocument $parent, private readonly TranslatorInterface $translator)
            {
                parent::__construct($parent);
            }

            public function render(): bool
            {
                $this->addColumn(PdfColumn::left('', 10.0));
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
}
