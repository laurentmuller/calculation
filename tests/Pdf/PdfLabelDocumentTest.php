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

use App\Pdf\Events\PdfLabelTextEvent;
use App\Pdf\Interfaces\PdfLabelTextListenerInterface;
use App\Pdf\PdfLabel;
use App\Pdf\PdfLabelDocument;
use App\Service\PdfLabelService;
use fpdf\PdfException;
use fpdf\PdfScaling;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[CoversClass(PdfLabelDocument::class)]
class PdfLabelDocumentTest extends TestCase
{
    public function testAddLabels(): void
    {
        $label = $this->getLabel('5160');
        $doc = new PdfLabelDocument($label);
        $doc->setLabelBorder(true);
        $doc->addLabel('');
        self::assertSame(1, $doc->getPage());

        $doc->setLabelBorder(false);
        for ($i = 0, $size = $label->size(); $i < $size; ++$i) {
            $doc->addLabel('text');
        }
        self::assertSame(2, $doc->getPage());
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
        self::expectException(PdfException::class);
        $label = clone $this->getLabel('3422');
        $label->fontSize = 1;
        new PdfLabelDocument($label);
    }

    public function testListener(): void
    {
        $listener = new class() implements PdfLabelTextListenerInterface {
            public function drawLabelText(PdfLabelTextEvent $event): bool
            {
                return 1 === $event->index % 2;
            }
        };

        $label = $this->getLabel('3422');
        $doc = new PdfLabelDocument($label);
        $doc->setLabelTextListener($listener);
        for ($i = 0; $i < 3; ++$i) {
            $doc->addLabel(\sprintf('text %d', $i));
        }
        self::assertSame(1, $doc->getPage());
    }

    private function getLabel(string $name): PdfLabel
    {
        $service = new PdfLabelService(new ArrayAdapter());

        return $service->get($name);
    }
}
