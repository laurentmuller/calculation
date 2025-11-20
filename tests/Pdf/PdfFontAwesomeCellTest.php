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

use App\Model\FontAwesomeImage;
use App\Pdf\PdfFontAwesomeCell;
use App\Pdf\PdfStyle;
use App\Tests\Fixture\FixturePdfImageDocument;
use fpdf\Enums\PdfMove;
use fpdf\PdfRectangle;
use PHPUnit\Framework\TestCase;

final class PdfFontAwesomeCellTest extends TestCase
{
    public function testIconCell(): void
    {
        $image = $this->getImage();
        $cell = new PdfFontAwesomeCell($image, 'Text');
        $bounds = new PdfRectangle(0, 0, 100, 5.0);

        $doc = $this->getDocument();
        $cell->output($doc, $bounds);
        self::assertSame(1, $doc->getPage());
    }

    public function testMoveBelow(): void
    {
        $image = $this->getImage();
        $cell = new PdfFontAwesomeCell($image, 'Text');
        $bounds = new PdfRectangle(0, 0, 100, 5.0);
        $doc = $this->getDocument();
        $cell->output($doc, $bounds, move: PdfMove::BELOW);
        $position = $doc->getPosition();
        self::assertSame(0.0, $position->x);
        self::assertSame(5.0, $position->y);
    }

    public function testMoveNewLine(): void
    {
        $image = $this->getImage();
        $cell = new PdfFontAwesomeCell($image, 'Text');
        $bounds = new PdfRectangle(0, 0, 100, 5.0);

        $doc = $this->getDocument();
        $cell->output($doc, $bounds, move: PdfMove::NEW_LINE);
        $position = $doc->getPosition();
        self::assertSame($doc->getLeftMargin(), $position->x);
        self::assertSame(5.0, $position->y);
    }

    public function testMoveRight(): void
    {
        $image = $this->getImage();
        $cell = new PdfFontAwesomeCell($image, 'Text');
        $bounds = new PdfRectangle(0, 0, 100, 5.0);
        $doc = $this->getDocument();
        $cell->output($doc, $bounds);
        $position = $doc->getPosition();
        self::assertSame(100.0, $position->x);
        self::assertSame(0.0, $position->y);
    }

    private function getDocument(): FixturePdfImageDocument
    {
        $doc = new FixturePdfImageDocument();
        PdfStyle::default()->apply($doc);

        return $doc->addPage();
    }

    private function getImage(): FontAwesomeImage
    {
        $path = __DIR__ . '/../files/images/example.png';
        $content = \file_get_contents($path);
        self::assertIsString($content);

        return new FontAwesomeImage($content, 64, 64, 96);
    }
}
