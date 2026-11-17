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

namespace App\Pdf\QrCode;

use Endroid\QrCode\Bacon\MatrixFactory;
use Endroid\QrCode\Color\ColorInterface;
use Endroid\QrCode\Label\LabelInterface;
use Endroid\QrCode\Logo\LogoInterface;
use Endroid\QrCode\QrCodeInterface;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Writer\WriterInterface;
use fpdf\Color\PdfRgbColor;
use fpdf\Enums\PdfFontName;
use fpdf\Enums\PdfFontStyle;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\Enums\PdfTextAlignment;
use fpdf\Enums\PdfUnit;
use fpdf\PdfDocument;
use fpdf\PdfSize;

final readonly class PdfWriter implements WriterInterface
{
    public const string WRITER_OPTION_LINK = 'link';
    public const string WRITER_OPTION_PDF = 'fpdf';
    public const string WRITER_OPTION_UNIT = 'unit';
    public const string WRITER_OPTION_X = 'x';
    public const string WRITER_OPTION_Y = 'y';

    #[\Override]
    public function write(QrCodeInterface $qrCode, ?LogoInterface $logo = null, ?LabelInterface $label = null, array $options = []): ResultInterface
    {
        $matrixFactory = new MatrixFactory();
        $matrix = $matrixFactory->create($qrCode);

        $unit = PdfUnit::MILLIMETER;
        if (isset($options[self::WRITER_OPTION_UNIT])) {
            $unit = PdfUnit::tryFrom($options[self::WRITER_OPTION_UNIT]) ?? throw new \Exception(\sprintf('Invalid PDF unit "%s".', $options[self::WRITER_OPTION_UNIT]));
        }

        $labelSpace = 0;
        if ($label instanceof LabelInterface) {
            $labelSpace = 30;
        }

        $foregroundColor = $qrCode->getForegroundColor();
        if ($foregroundColor->getAlpha() > 0) {
            throw new \Exception('PDF Writer does not support alpha channels.');
        }
        $backgroundColor = $qrCode->getBackgroundColor();
        if ($backgroundColor->getAlpha() > 0) {
            throw new \Exception('PDF Writer does not support alpha channels.');
        }

        if (isset($options[self::WRITER_OPTION_PDF])) {
            $document = $options[self::WRITER_OPTION_PDF];
            if (!$document instanceof PdfDocument) {
                throw new \Exception('pdf option must be an instance of PdfDocument.');
            }
        } else {
            $size = new PdfSize($matrix->getOuterSize(), $matrix->getOuterSize() + $labelSpace);
            $document = new PdfDocument(unit: $unit, size: $size);
            $document->addPage();
        }

        $x = 0;
        if (isset($options[self::WRITER_OPTION_X])) {
            $x = $options[self::WRITER_OPTION_X];
        }
        $y = 0;
        if (isset($options[self::WRITER_OPTION_Y])) {
            $y = $options[self::WRITER_OPTION_Y];
        }

        $document->setFillColor($this->getColor($backgroundColor));
        $document->rect($x, $y, $matrix->getOuterSize(), $matrix->getOuterSize(), PdfRectangleStyle::FILL);
        $document->setFillColor($this->getColor($foregroundColor));

        for ($rowIndex = 0; $rowIndex < $matrix->getBlockCount(); ++$rowIndex) {
            for ($columnIndex = 0; $columnIndex < $matrix->getBlockCount(); ++$columnIndex) {
                if (1 === $matrix->getBlockValue($rowIndex, $columnIndex)) {
                    $document->rect(
                        $x + $matrix->getMarginLeft() + ($columnIndex * $matrix->getBlockSize()),
                        $y + $matrix->getMarginLeft() + ($rowIndex * $matrix->getBlockSize()),
                        $matrix->getBlockSize(),
                        $matrix->getBlockSize(),
                        PdfRectangleStyle::FILL
                    );
                }
            }
        }

        if ($logo instanceof LogoInterface) {
            $this->addLogo($logo, $document, $x, $y, $matrix->getOuterSize());
        }

        if ($label instanceof LabelInterface) {
            $document->setXY($x, $y + $matrix->getOuterSize() + $labelSpace - 25);
            $document->setFont(PdfFontName::HELVETICA, PdfFontStyle::REGULAR, $label->getFont()->getSize());
            $document->cell(
                width: $matrix->getOuterSize(),
                text: $label->getText(),
                align: PdfTextAlignment::CENTER
            );
        }

        if (isset($options[self::WRITER_OPTION_LINK])) {
            $link = $options[self::WRITER_OPTION_LINK];
            $document->link($x, $y, $x + $matrix->getOuterSize(), $y + $matrix->getOuterSize(), $link);
        }

        return new PdfResult($matrix, $document);
    }

    private function addLogo(LogoInterface $logo, PdfDocument $document, float $x, float $y, float $size): void
    {
        $logoPath = $logo->getPath();
        $logoHeight = $logo->getResizeToHeight();
        $logoWidth = $logo->getResizeToWidth();

        if (null === $logoHeight || null === $logoWidth) {
            $imageSize = \getimagesize($logoPath);
            if (false === $imageSize) {
                throw new \Exception(\sprintf('Unable to read image size for logo "%s".', $logoPath));
            }
            [$logoSourceWidth, $logoSourceHeight] = $imageSize;

            if (null === $logoWidth) {
                $logoWidth = $logoSourceWidth;
            }

            if (null === $logoHeight) {
                $aspectRatio = $logoWidth / $logoSourceWidth;
                $logoHeight = (int) ($logoSourceHeight * $aspectRatio);
            }
        }

        $logoX = $x + $size / 2 - $logoWidth / 2;
        $logoY = $y + $size / 2 - $logoHeight / 2;

        $document->image($logoPath, $logoX, $logoY, $logoWidth, $logoHeight);
    }

    private function getColor(ColorInterface $color): PdfRgbColor
    {
        return new PdfRgbColor(
            $color->getRed(),
            $color->getGreen(),
            $color->getBlue(),
        );
    }
}
