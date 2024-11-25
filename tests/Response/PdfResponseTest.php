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

namespace App\Tests\Response;

use App\Response\PdfResponse;
use fpdf\PdfDocument;
use PHPUnit\Framework\TestCase;

class PdfResponseTest extends TestCase
{
    public function testGetAttachmentMimeType(): void
    {
        $doc = new PdfDocument();
        $response = new PdfResponse($doc);
        $actual = $response->getAttachmentMimeType();
        self::assertSame('application/x-download', $actual);
    }

    public function testGetFileExtension(): void
    {
        $doc = new PdfDocument();
        $response = new PdfResponse($doc);
        self::assertSame('pdf', $response->getFileExtension());
    }

    public function testValidate(): void
    {
        $name = 'document.pdf';
        $doc = new PdfDocument();
        $response = new PdfResponse($doc, name: $name);
        $headers = $response->headers;
        self::assertTrue($headers->has('Content-Disposition'));
        $actual = $headers->get('Content-Disposition');
        self::assertIsString($actual);
        self::assertStringContainsString($name, $actual);
    }
}
