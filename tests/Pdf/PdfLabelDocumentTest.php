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

namespace App\Tests\Pdf;

use App\Pdf\PdfLabel;
use App\Pdf\PdfLabelDocument;
use App\Pdf\PdfLabelItem;
use App\Service\PdfLabelService;
use fpdf\Enums\PdfScaling;
use fpdf\PdfException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class PdfLabelDocumentTest extends TestCase
{
    private PdfLabelService $service;

    #[\Override]
    protected function setUp(): void
    {
        $this->service = new PdfLabelService(
            file: __DIR__ . '/../../resources/data/labels.json',
            cache: new ArrayAdapter()
        );
    }

    public function testConstructor(): void
    {
        $label = $this->getLabel('3422');
        $doc = new PdfLabelDocument($label);

        $expected = PdfScaling::NONE;
        $actual = $doc->getViewerPreferences()->getScaling();
        self::assertSame($expected, $actual);

        $expected = 0.0;
        self::assertSame($expected, $doc->getCellMargin());
        self::assertSame($expected, $doc->getTopMargin());
        self::assertSame($expected, $doc->getLeftMargin());
        self::assertSame($expected, $doc->getRightMargin());

        $expected = $label->pageSize->getWidth();
        self::assertEqualsWithDelta($expected, $doc->getPageWidth(), 0.01);
        $expected = $label->pageSize->getHeight();
        self::assertEqualsWithDelta($expected, $doc->getPageHeight(), 0.01);
        $expected = $label->fontSize;
        self::assertEqualsWithDelta($expected, $doc->getFontSizeInPoint(), 0.01);
    }

    public function testInvalidFontSize(): void
    {
        $label = new PdfLabel(
            name: 'Fake',
            cols: 1,
            rows: 1,
            width: 100,
            height: 100,
            fontSize: 0, // @phpstan-ignore argument.type
        );
        $this->expectException(PdfException::class);
        $this->expectExceptionMessageMatches('/Invalid font size: 0\. Allowed sizes: \[.*\]\./');
        new PdfLabelDocument($label);
    }

    public function testOutputEmptyLabel(): void
    {
        $label = $this->getLabel('5160');
        $doc = new PdfLabelDocument($label);
        $doc->outputLabel('');
        self::assertSame(0, $doc->getPage());
    }

    public function testOutputLabelWithBorder(): void
    {
        $label = $this->getLabel('5160');
        $doc = new PdfLabelDocument($label);
        $doc->setLabelBorder(true);
        $values = [
            'Text1',
            PdfLabelItem::instance('Text2'),
            null,
        ];
        for ($i = 0, $size = $label->size() + 1; $i < $size; ++$i) {
            $doc->outputLabel($values);
        }
        self::assertSame(2, $doc->getPage());
    }

    private function getLabel(string $name): PdfLabel
    {
        return $this->service->get($name);
    }
}
