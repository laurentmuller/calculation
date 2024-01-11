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

namespace App\Pdf;

use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Enums\PdfDocumentSize;
use App\Pdf\Enums\PdfDocumentUnit;
use App\Pdf\Interfaces\PdfLabelTextListenerInterface;
use App\Pdf\Traits\PdfDashLineTrait;
use App\Utils\StringUtils;

/**
 * PDF document to output labels.
 *
 * @psalm-type LabelType = array{
 *     pageSize: 'A3'|'A4'|'A5'|'Legal'|'Letter',
 *     unit: 'in'|'mm',
 *     marginLeft: float,
 *     marginTop: float,
 *     cols: positive-int,
 *     rows: positive-int,
 *     spaceX: float,
 *     spaceY: float,
 *     width: float,
 *     height: float,
 *     fontSize: int<6, 15>}
 **/
class PdfLabelDocument extends PdfDocument
{
    use PdfDashLineTrait;

    /**
     * Avery formats.
     */
    public const AVERY_FORMATS = [
        '3422' => ['pageSize' => 'A4',      'unit' => 'mm', 'marginLeft' => 0,      'marginTop' => 8.5,     'cols' => 3, 'rows' => 8,   'spaceX' => 0,      'spaceY' => 0,   'width' => 70,      'height' => 35,    'fontSize' => 9],
        '5160' => ['pageSize' => 'Letter',  'unit' => 'mm', 'marginLeft' => 1.762,  'marginTop' => 10.7,    'cols' => 3, 'rows' => 10,  'spaceX' => 3.175,  'spaceY' => 0,   'width' => 66.675,  'height' => 25.4,  'fontSize' => 8],
        '5161' => ['pageSize' => 'Letter',  'unit' => 'mm', 'marginLeft' => 6.4,    'marginTop' => 12.7,    'cols' => 2, 'rows' => 10,  'spaceX' => 4.8,    'spaceY' => 0,   'width' => 101.6,   'height' => 25.4,  'fontSize' => 8],
        '5162' => ['pageSize' => 'Letter',  'unit' => 'mm', 'marginLeft' => 0.97,   'marginTop' => 20.224,  'cols' => 2, 'rows' => 7,   'spaceX' => 4.762,  'spaceY' => 0,   'width' => 100.807, 'height' => 35.72, 'fontSize' => 8],
        '5163' => ['pageSize' => 'Letter',  'unit' => 'mm', 'marginLeft' => 1.762,  'marginTop' => 10.7,    'cols' => 2, 'rows' => 5,   'spaceX' => 3.175,  'spaceY' => 0,   'width' => 101.6,   'height' => 50.8,  'fontSize' => 8],
        '5164' => ['pageSize' => 'Letter',  'unit' => 'in', 'marginLeft' => 0.148,  'marginTop' => 0.5,     'cols' => 2, 'rows' => 3,   'spaceX' => 0.2031, 'spaceY' => 0,   'width' => 4.0,     'height' => 3.33,  'fontSize' => 12],
        '8600' => ['pageSize' => 'Letter',  'unit' => 'mm', 'marginLeft' => 7.1,    'marginTop' => 19,      'cols' => 3, 'rows' => 10,  'spaceX' => 9.5,    'spaceY' => 3.1, 'width' => 66.6,    'height' => 25.4,  'fontSize' => 8],
        'L7163' => ['pageSize' => 'A4',     'unit' => 'mm', 'marginLeft' => 5,      'marginTop' => 15,      'cols' => 2, 'rows' => 7,   'spaceX' => 25,     'spaceY' => 0,   'width' => 99.1,    'height' => 38.1,  'fontSize' => 9],
    ];

    private const FONT_CONVERSION = [
        6 => 2.0,
        7 => 2.5,
        8 => 3.0,
        9 => 4.0,
        10 => 5.0,
        11 => 6.0,
        12 => 7.0,
        13 => 8.0,
        14 => 9.0,
        15 => 10.0,
    ];

    private const UNIT_CONVERSION = [
        'in' => 39.37008,
        'mm' => 1000.0,
    ];

    // the number of labels horizontally
    private int $cols = 0;
    // the current column (0 based index)
    private int $currentCol;
    // the current row (0 based index)
    private int $currentRow;
    // the height of label
    private float $height = 0;
    // the draw border around labels
    private bool $labelBorder = false;
    // the label listener
    private ?PdfLabelTextListenerInterface $labelTextListener = null;
    // the Line height
    private float $lineHeight = 0;
    // the left margin of labels
    private float $marginLeft = 0;
    // the top margin of labels
    private float $marginTop = 0;
    // the padding inside labels
    private float $padding = 0;
    // the number of labels vertically
    private int $rows = 0;
    // the horizontal space between 2 labels
    private float $spaceX = 0;
    // the vertical space between 2 labels
    private float $spaceY = 0;
    // the document unit
    private string $unit;
    // the width of label
    private float $width = 0;

    /**
     * @param array|string $format     a label type or one of the predefined format name
     * @param string       $unit       the user unit. Must be 'in' or 'mm'.
     * @param int          $startIndex the zero based index of the first label
     *
     * @psalm-param LabelType|string $format
     * @psalm-param 'in'|'mm' $unit
     * @psalm-param non-negative-int $startIndex
     *
     * @throws PdfException if the format name is unknown or if the format array contain invalid value
     */
    public function __construct(array|string $format, string $unit = 'mm', int $startIndex = 0)
    {
        if (\is_string($format)) {
            if (!isset(self::AVERY_FORMATS[$format])) {
                $formats = \implode(', ', \array_keys(self::AVERY_FORMATS));
                $this->Error(\sprintf('Unknown label format: %s. Allowed formats: %s.', $format, $formats));
            }
            $format = self::AVERY_FORMATS[$format];
        }

        $documentUnit = $this->_getDocumentUnit($unit);
        $documentSize = $this->_getDocumentSize($format);
        parent::__construct(unit: $documentUnit, size: $documentSize);

        $this->unit = $documentUnit->value;
        $this->_setFormat($format);
        $this->SetFont('Arial');
        $this->SetMargins(0, 0);
        $this->SetAutoPageBreak(false);

        $col = 1 + $startIndex % $this->cols;
        if ($col > $this->cols) {
            $this->Error(\sprintf('Invalid starting column: %d. Maximum allowed: %d.', $col, $this->cols));
        }
        $row = 1 + \intdiv($startIndex, $this->cols);
        if ($row > $this->rows) {
            $this->Error(\sprintf('Invalid starting row: %d. Maximum allowed: %d.', $row, $this->rows));
        }
        $this->currentCol = $col - 1;
        $this->currentRow = $row - 1;
        $this->cMargin = 0;
    }

    /**
     * Output a label.
     */
    public function addLabel(string $text): static
    {
        if (0 === $this->page) {
            $this->AddPage();
        } elseif ($this->currentRow === $this->rows) {
            $this->currentRow = 0;
            $this->AddPage();
        }
        $this->_outputLabelText($text);
        $this->_outputLabelBorder();
        if (++$this->currentCol === $this->cols) {
            $this->currentCol = 0;
            ++$this->currentRow;
        }

        return $this;
    }

    /**
     * This implementation skip output footer.
     */
    public function Footer(): void
    {
    }

    /**
     * This implementation skip output header.
     */
    public function Header(): void
    {
    }

    /**
     * Sets a value indicating if a dash border is draw around labels.
     */
    public function setLabelBorder(bool $labelBorder): static
    {
        $this->labelBorder = $labelBorder;

        return $this;
    }

    /**
     * Sets the draw label texts listener.
     */
    public function setLabelTextListener(?PdfLabelTextListenerInterface $labelTextListener): static
    {
        $this->labelTextListener = $labelTextListener;

        return $this;
    }

    protected function _putcatalog(): void
    {
        parent::_putcatalog();
        $this->_put('/ViewerPreferences <</PrintScaling /None>>');
    }

    /**
     * @psalm-param 'in'|'mm' $src
     */
    private function _convertUnit(float $value, string $src): float
    {
        if ($src !== $this->unit) {
            return $value * self::UNIT_CONVERSION[$this->unit] / self::UNIT_CONVERSION[$src];
        }

        return $value;
    }

    /**
     * @psalm-param LabelType $format
     *
     * @throws PdfException
     */
    private function _getDocumentSize(array $format): PdfDocumentSize
    {
        $pageSize = $format['pageSize'];

        try {
            return PdfDocumentSize::from($pageSize);
        } catch (\ValueError $e) {
            throw new PdfException(\sprintf('Invalid page size: %s.', $pageSize), $e->getCode(), $e);
        }
    }

    /**
     * @throws PdfException
     */
    private function _getDocumentUnit(string $unit): PdfDocumentUnit
    {
        try {
            return PdfDocumentUnit::from($unit);
        } catch (\ValueError $e) {
            throw new PdfException(\sprintf('Invalid unit: %s.', $unit), $e->getCode(), $e);
        }
    }

    /**
     * Give the line height for a given font size.
     *
     * @throws PdfException
     */
    private function _getHeightChars(int $pt): float
    {
        if (!isset(self::FONT_CONVERSION[$pt])) {
            $sizes = \implode(', ', \array_keys(self::FONT_CONVERSION));
            $this->Error(\sprintf('Invalid font size: %d. Allowed sizes: %s', $pt, $sizes));
        }

        return $this->_convertUnit(self::FONT_CONVERSION[$pt], 'mm');
    }

    private function _getLabelX(): float
    {
        return $this->marginLeft + (float) $this->currentCol * ($this->width + $this->spaceX) + $this->padding;
    }

    private function _getLabelY(string $text): float
    {
        $lines = $this->getLinesCount($text, $this->width - 2.0 * $this->padding);
        $height = (float) $lines * $this->lineHeight;
        $y = ($this->height - $height) / 2.0;

        return $this->marginTop + (float) $this->currentRow * ($this->height + $this->spaceY) + $y;
    }

    private function _outputLabelBorder(): void
    {
        if (!$this->labelBorder) {
            return;
        }
        $x = $this->marginLeft + (float) $this->currentCol * ($this->width + $this->spaceX);
        $y = $this->marginTop + (float) $this->currentRow * ($this->height + $this->spaceY);
        PdfDrawColor::cellBorder()->apply($this);
        $this->dashedRect($x, $y, $this->width, $this->height);
    }

    private function _outputLabelText(string $text): void
    {
        if ('' === $text) {
            return;
        }

        $x = $this->_getLabelX();
        $y = $this->_getLabelY($text);
        $w = $this->width - $this->padding;
        if (!$this->labelTextListener instanceof PdfLabelTextListenerInterface) {
            $this->SetXY($x, $y);
            $this->MultiCell(w: $w, h: $this->lineHeight, txt: $text, align: 'L');

            return;
        }

        $texts = \explode(StringUtils::NEW_LINE, $text);
        $lines = \count($texts);
        foreach ($texts as $index => $text) {
            $this->SetXY($x, $y);
            // @phpstan-ignore-next-line
            if (!$this->labelTextListener->drawLabelText($this, $text, $index, $lines, $w, $this->lineHeight)) {
                $this->Cell($w, $this->lineHeight, $text);
            }
            $y += $this->lasth;
        }
    }

    /**
     * @throws PdfException
     */
    private function _setFontSize(int $pt): void
    {
        $this->lineHeight = $this->_getHeightChars($pt);
        $this->SetFontSize($pt);
    }

    /**
     * @psalm-param LabelType $format
     *
     * @throws PdfException
     */
    private function _setFormat(array $format): void
    {
        $unit = $format['unit'];
        $this->cols = $format['cols'];
        $this->rows = $format['rows'];
        $this->marginLeft = $this->_convertUnit($format['marginLeft'], $unit);
        $this->marginTop = $this->_convertUnit($format['marginTop'], $unit);
        $this->spaceX = $this->_convertUnit($format['spaceX'], $unit);
        $this->spaceY = $this->_convertUnit($format['spaceY'], $unit);
        $this->width = $this->_convertUnit($format['width'], $unit);
        $this->height = $this->_convertUnit($format['height'], $unit);
        $this->padding = $this->_convertUnit(3, 'mm');
        $this->_setFontSize($format['fontSize']);
    }
}
