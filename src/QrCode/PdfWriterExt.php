<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\QrCode;

use Endroid\QrCode\Bacon\MatrixFactory;
use Endroid\QrCode\Label\LabelInterface;
use Endroid\QrCode\Logo\LogoInterface;
use Endroid\QrCode\QrCodeInterface;
use Endroid\QrCode\Writer\Result\PdfResult;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Writer\WriterInterface;

final class PdfWriterExt implements WriterInterface
{
    // @phpstan-ignore-next-line
    public const WRITER_OPTION_PDF = 'fpdf';
    public const WRITER_OPTION_UNIT = 'unit';
    public const WRITER_OPTION_X = 'x';
    public const WRITER_OPTION_Y = 'y';

    public function write(QrCodeInterface $qrCode, LogoInterface $logo = null, LabelInterface $label = null, array $options = []): ResultInterface
    {
        if (!\class_exists(\FPDF::class)) {
            throw new \Exception('Unable to find FPDF: check your installation');
        }

        $foregroundColor = $qrCode->getForegroundColor();
        if ($foregroundColor->getAlpha() > 0) {
            throw new \Exception('PDF Writer does not support alpha channels');
        }

        $backgroundColor = $qrCode->getBackgroundColor();
        if ($backgroundColor->getAlpha() > 0) {
            throw new \Exception('PDF Writer does not support alpha channels');
        }

        $unit = 'mm';
        if (isset($options[self::WRITER_OPTION_UNIT])) {
            $unit = $options[self::WRITER_OPTION_UNIT];
        }

        $allowedUnits = ['mm', 'pt', 'cm', 'in'];
        if (!\in_array($unit, $allowedUnits, true)) {
            throw new \Exception(\sprintf('PDF Measure unit should be one of [%s]', \implode(', ', $allowedUnits)));
        }

        $matrixFactory = new MatrixFactory();
        $matrix = $matrixFactory->create($qrCode);

        $labelSpace = 0;
        if ($label instanceof LabelInterface) {
            $labelSpace = 30;
        }

        $outerSize = $matrix->getOuterSize();
        $blockCount = $matrix->getBlockCount();
        $marginLeft = $matrix->getMarginLeft();
        $blockSize = $matrix->getBlockSize();

        if (isset($options[self::WRITER_OPTION_PDF])) {
            $fpdf = $options[self::WRITER_OPTION_PDF];
            if (!$fpdf instanceof \FPDF) {
                throw new \Exception('fpdf option must be an instance of FPDF');
            }
        } else {
            $fpdf = new \FPDF('P', $unit, [$outerSize, $outerSize + $labelSpace]);
            $fpdf->AddPage();
        }

        $x = 0;
        if (isset($options[self::WRITER_OPTION_X])) {
            $x = (int) $options[self::WRITER_OPTION_X];
        }
        $y = 0;
        if (isset($options[self::WRITER_OPTION_Y])) {
            $y = (int) $options[self::WRITER_OPTION_Y];
        }

        $fpdf->SetFillColor($backgroundColor->getRed(), $backgroundColor->getGreen(), $backgroundColor->getBlue());
        $fpdf->Rect($x, $y, $outerSize, $outerSize, 'F');
        $fpdf->SetFillColor($foregroundColor->getRed(), $foregroundColor->getGreen(), $foregroundColor->getBlue());

        for ($rowIndex = 0; $rowIndex < $blockCount; ++$rowIndex) {
            for ($columnIndex = 0; $columnIndex < $blockCount; ++$columnIndex) {
                if (1 === $matrix->getBlockValue($rowIndex, $columnIndex)) {
                    $fpdf->Rect(
                        $x + $marginLeft + ($columnIndex * $blockSize),
                        $y + $marginLeft + ($rowIndex * $blockSize),
                        $blockSize,
                        $blockSize,
                        'F'
                    );
                }
            }
        }

        if ($logo instanceof LogoInterface) {
            $this->addLogo($logo, $fpdf, $x, $y, $outerSize);
        }

        if ($label instanceof LabelInterface) {
            $this->addLabel($label, $fpdf, $x, $y + $outerSize + $labelSpace - 25, $outerSize);
        }

        return new PdfResult($fpdf);
    }

    private function addLabel(LabelInterface $label, \FPDF $fpdf, int $x, int $y, int $size): void
    {
        $fpdf->SetXY($x, $y);
        $fpdf->SetFont('Helvetica', null, $label->getFont()->getSize());
        $fpdf->Cell($size, 0, $label->getText(), 0, 0, 'C');
    }

    private function addLogo(LogoInterface $logo, \FPDF $fpdf, int $x, int $y, int $size): void
    {
        $logoPath = $logo->getPath();
        $logoHeight = $logo->getResizeToHeight();
        $logoWidth = $logo->getResizeToWidth();

        if (null === $logoHeight || null === $logoWidth) {
            // @phpstan-ignore-next-line
            [$logoSourceWidth, $logoSourceHeight] = \getimagesize($logoPath);

            if (null === $logoWidth) {
                $logoWidth = (int) $logoSourceWidth;
            }

            if (null === $logoHeight) {
                $aspectRatio = $logoWidth / $logoSourceWidth;
                $logoHeight = (int) ($logoSourceHeight * $aspectRatio);
            }
        }

        $logoX = $x + $size / 2 - (int) $logoWidth / 2;
        $logoY = $y + $size / 2 - (int) $logoHeight / 2;

        $fpdf->Image($logoPath, $logoX, $logoY, $logoWidth, $logoHeight);
    }
}
