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
use Endroid\QrCode\Matrix\MatrixInterface;
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

final readonly class PdfDocumentWriter implements WriterInterface
{
    public const string WRITER_OPTION_DOCUMENT = 'document';
    public const string WRITER_OPTION_LINK = 'link';
    public const string WRITER_OPTION_UNIT = 'unit';
    public const string WRITER_OPTION_X = 'x';
    public const string WRITER_OPTION_Y = 'y';

    #[\Override]
    public function write(
        QrCodeInterface $qrCode,
        ?LogoInterface $logo = null,
        ?LabelInterface $label = null,
        array $options = []
    ): ResultInterface {
        $matrixFactory = new MatrixFactory();
        $matrix = $matrixFactory->create($qrCode);

        $unit = PdfUnit::MILLIMETER;
        if (isset($options[self::WRITER_OPTION_UNIT])) {
            $value = $options[self::WRITER_OPTION_UNIT];
            $unit = PdfUnit::tryFrom($value) ?? throw new \InvalidArgumentException(\sprintf('Invalid unit option: "%s".', $value));
        }

        $outerSize = $matrix->getOuterSize();
        $labelSpace = $label instanceof LabelInterface ? 30 : 0;
        $foregroundColor = $this->convertColor($qrCode->getForegroundColor());
        $backgroundColor = $this->convertColor($qrCode->getBackgroundColor());

        if (isset($options[self::WRITER_OPTION_DOCUMENT])) {
            $document = $options[self::WRITER_OPTION_DOCUMENT];
            if (!$document instanceof PdfDocument) {
                throw new \InvalidArgumentException(\sprintf('Invalid document option: "%s".', \get_debug_type($document)));
            }
        } else {
            $size = new PdfSize($outerSize, $outerSize + $labelSpace);
            $document = new PdfDocument(unit: $unit, size: $size);
            $document->addPage();
        }

        $x = $options[self::WRITER_OPTION_X] ?? 0;
        $y = $options[self::WRITER_OPTION_Y] ?? 0;

        $document->setFillColor($backgroundColor);
        $document->rect($x, $y, $outerSize, $outerSize, PdfRectangleStyle::FILL);

        $document->setFillColor($foregroundColor);
        $this->addMatrix($matrix, $document, $x, $y);

        if ($logo instanceof LogoInterface) {
            $this->addLogo($logo, $document, $x, $y, $outerSize);
        }

        if ($label instanceof LabelInterface) {
            $this->addLabel($label, $document, $x, $y + $outerSize + $labelSpace - 25, $outerSize);
        }

        if (isset($options[self::WRITER_OPTION_LINK])) {
            $document->link($x, $y, $x + $outerSize, $y + $outerSize + $labelSpace, $options[self::WRITER_OPTION_LINK]);
        }

        return new PdfDocumentResult($matrix, $document);
    }

    private function addLabel(LabelInterface $label, PdfDocument $document, float $x, float $y, float $width): void
    {
        $document->setXY($x, $y);
        $document->setFont(PdfFontName::HELVETICA, PdfFontStyle::REGULAR, $label->getFont()->getSize());
        $document->cell(
            width: $width,
            text: $label->getText(),
            align: PdfTextAlignment::CENTER
        );
    }

    private function addLogo(LogoInterface $logo, PdfDocument $document, float $x, float $y, float $size): void
    {
        $logoPath = $logo->getPath();
        $logoHeight = $logo->getResizeToHeight();
        $logoWidth = $logo->getResizeToWidth();

        if (null === $logoHeight || null === $logoWidth) {
            $imageSize = \getimagesize($logoPath);
            if (false === $imageSize) {
                throw new \InvalidArgumentException(\sprintf('Unable to read image size for logo: "%s".', $logoPath));
            }
            [$sourceWidth, $sourceHeight] = $imageSize;

            $logoWidth ??= $sourceWidth;
            if (null === $logoHeight) {
                $aspectRatio = $logoWidth / $sourceWidth;
                $logoHeight = (int) ($sourceHeight * $aspectRatio);
            }
        }

        $logoX = $x + ($size - $logoWidth) / 2.0;
        $logoY = $y + ($size - $logoHeight) / 2.0;

        $document->image($logoPath, $logoX, $logoY, $logoWidth, $logoHeight);
    }

    private function addMatrix(MatrixInterface $matrix, PdfDocument $document, float $x, float $y): void
    {
        $blocSize = $matrix->getBlockSize();
        $marginLeft = $matrix->getMarginLeft();
        $blockCount = $matrix->getBlockCount();
        for ($rowIndex = 0; $rowIndex < $blockCount; ++$rowIndex) {
            for ($columnIndex = 0; $columnIndex < $blockCount; ++$columnIndex) {
                if (1 !== $matrix->getBlockValue($rowIndex, $columnIndex)) {
                    continue;
                }
                $document->rect(
                    $x + $marginLeft + ($blocSize * $columnIndex),
                    $y + $marginLeft + ($blocSize * $rowIndex),
                    $blocSize,
                    $blocSize,
                    PdfRectangleStyle::FILL
                );
            }
        }
    }

    private function convertColor(ColorInterface $color): PdfRgbColor
    {
        if ($color->getAlpha() > 0) {
            throw new \InvalidArgumentException(\sprintf('%s does not support alpha channels.', self::class));
        }

        return PdfRgbColor::instance($color->getRed(), $color->getGreen(), $color->getBlue());
    }
}
