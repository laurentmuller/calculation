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
use App\Pdf\PdfIconCell;
use App\Pdf\PdfStyle;
use App\Tests\Data\PdfImageDocument;
use fpdf\PdfRectangle;
use PHPUnit\Framework\TestCase;

class PdfIconCellTest extends TestCase
{
    public function testIconCell(): void
    {
        $image = $this->getImage();
        $cell = new PdfIconCell($image, 'Text');
        $bounds = new PdfRectangle(0, 0, 100, 5.0);

        $doc = $this->getDocument();
        $cell->drawImage($doc, $bounds);
        self::assertSame(1, $doc->getPage());
    }

    private function getDocument(): PdfImageDocument
    {
        $doc = new PdfImageDocument();
        PdfStyle::default()->apply($doc);

        return $doc->addPage();
    }

    private function getImage(): FontAwesomeImage
    {
        $path = __DIR__ . '/../Data/images/example.png';
        $content = \file_get_contents($path);
        self::assertIsString($content);

        return new FontAwesomeImage($content, 64, 64, 96);
    }
}
