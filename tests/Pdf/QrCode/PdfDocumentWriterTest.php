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

namespace App\Tests\Pdf\QrCode;

use App\Pdf\QrCode\PdfDocumentResult;
use App\Pdf\QrCode\PdfDocumentWriter;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Color\ColorInterface;
use Endroid\QrCode\Exception\ValidationException;
use Endroid\QrCode\Writer\Result\ResultInterface;
use fpdf\PdfDocument;
use PHPUnit\Framework\TestCase;

final class PdfDocumentWriterTest extends TestCase
{
    /**
     * @throws ValidationException
     */
    public function testInvalidBackground(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessageIsOrContains(PdfDocumentWriter::class . ' does not support alpha channels.');
        $this->build(backgroundColor: new Color(0, 0, 0, 127));
    }

    /**
     * @throws ValidationException
     */
    public function testInvalidDocument(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessageIsOrContains('Invalid document option: "stdClass".');
        $this->build(writerOptions: [PdfDocumentWriter::WRITER_OPTION_DOCUMENT => new \stdClass()]);
    }

    /**
     * @throws ValidationException
     */
    public function testInvalidForeground(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessageIsOrContains(PdfDocumentWriter::class . ' does not support alpha channels.');
        $this->build(foregroundColor: new Color(0, 0, 0, 127));
    }

    /**
     * @throws ValidationException
     */
    public function testInvalidLogo(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessageMatches('/Unable to read image size for logo:.*/');
        $this->build(logoPath: __FILE__);
    }

    /**
     * @throws ValidationException
     */
    public function testInvalidUnit(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessageIsOrContains('Invalid unit option: "fake".');
        $this->build(writerOptions: [PdfDocumentWriter::WRITER_OPTION_UNIT => 'fake']);
    }

    /**
     * @throws ValidationException
     */
    public function testValidResult(): void
    {
        $result = $this->build(
            writerOptions: [PdfDocumentWriter::WRITER_OPTION_LINK => 'https://www.example.com'],
            data: 'Valid result',
            labelText: 'Label',
            logoPath: __DIR__ . '/../../files/images/example.png'
        );
        self::assertInstanceOf(PdfDocumentResult::class, $result);
        self::assertInstanceOf(PdfDocument::class, $result->getPdfDocument());
        self::assertSame('application/pdf', $result->getMimeType());
        self::assertStringStartsWith('%PDF-1.3', $result->getString());
    }

    /**
     * @throws ValidationException
     */
    private function build(
        ?array $writerOptions = null,
        ?string $data = null,
        ?ColorInterface $foregroundColor = null,
        ?ColorInterface $backgroundColor = null,
        ?string $labelText = null,
        ?string $logoPath = null,
    ): ResultInterface {
        return new Builder()->build(
            writer: new PdfDocumentWriter(),
            writerOptions: $writerOptions,
            data: $data,
            foregroundColor: $foregroundColor,
            backgroundColor: $backgroundColor,
            labelText: $labelText,
            logoPath: $logoPath
        );
    }
}
