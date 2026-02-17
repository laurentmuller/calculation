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
        $this->service = new PdfLabelService(new ArrayAdapter());
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
        self::expectExceptionMessageMatches('/Invalid font size: 1.*/');
        $label = $this->getLabel('3422')
            ->copy(fontSize: 1); // @phpstan-ignore argument.type
        new PdfLabelDocument($label);
    }

    public function testListener(): void
    {
        $listener = new class implements PdfLabelTextListenerInterface {
            #[\Override]
            public function drawLabelText(PdfLabelTextEvent $event): bool
            {
                return 1 === $event->index % 2;
            }
        };

        $label = $this->getLabel('3422');
        $doc = new PdfLabelDocument($label);
        $doc->setLabelTextListener($listener);
        for ($i = 0; $i < 3; ++$i) {
            $doc->outputLabel(\sprintf('text %d', $i));
        }
        self::assertSame(1, $doc->getPage());
    }

    public function testOutputLabel(): void
    {
        $label = $this->getLabel('5160');
        $doc = new PdfLabelDocument($label);
        $doc->setLabelBorder(true);
        self::assertSame(['column' => 0, 'row' => 0], $doc->getCurrentPosition());
        $doc->outputLabel('');
        self::assertSame(1, $doc->getPage());
        self::assertSame(['column' => 1, 'row' => 0], $doc->getCurrentPosition());

        $doc->setLabelBorder(false);
        for ($i = 0, $size = $label->size(); $i < $size; ++$i) {
            $doc->outputLabel(['Text1', 'Text1']);
        }
        self::assertSame(2, $doc->getPage());
    }

    private function getLabel(string $name): PdfLabel
    {
        return $this->service->get($name);
    }
}
