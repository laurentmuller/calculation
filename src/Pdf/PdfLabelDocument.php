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
use App\Pdf\Events\PdfLabelTextEvent;
use App\Pdf\Interfaces\PdfLabelTextListenerInterface;
use App\Pdf\Traits\PdfDashLineTrait;
use App\Utils\FileUtils;
use App\Utils\StringUtils;
use fpdf\PdfException;
use fpdf\PdfFontName;
use fpdf\PdfPageSize;
use fpdf\PdfTextAlignment;
use fpdf\PdfUnit;

/**
 * PDF document to output labels.
 *
 * @psalm-type LabelType = array{
 *     pageSize: string,
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

    // the number of horizontal labels
    private int $cols = 0;
    // the current column (0 based index)
    private int $currentCol;
    // the current row (0 based index)
    private int $currentRow;
    // the draw border around labels
    private bool $labelBorder = false;
    // the height of label
    private float $labelHeight = 0;
    // the label listener
    private ?PdfLabelTextListenerInterface $labelTextListener = null;
    // the width of label
    private float $labelWidth = 0;
    // the line height
    private float $lineHeight = 0;
    // the left margin of labels
    private float $marginLeft = 0;
    // the top margin of labels
    private float $marginTop = 0;
    // the padding inside labels
    private float $padding = 0;
    // the number of vertical labels
    private int $rows = 0;
    // the horizontal space between 2 labels
    private float $spaceX = 0;
    // the vertical space between 2 labels
    private float $spaceY = 0;
    // the document unit
    private string $unit;

    /**
     * @param array|string $format     a label type or one of the predefined format names
     * @param PdfUnit      $unit       the user unit
     * @param int          $startIndex the zero-based index of the first label
     *
     * @psalm-param LabelType|string $format
     * @psalm-param non-negative-int $startIndex
     *
     * @throws PdfException if the format name is unknown, or if the format array contains invalid value
     */
    public function __construct(array|string $format, PdfUnit $unit = PdfUnit::MILLIMETER, int $startIndex = 0)
    {
        if (\is_string($format)) {
            $format = $this->getAveryFormat($format);
        }
        parent::__construct(unit: $unit, size: $this->getDocumentSize($format));

        $this->unit = $unit->value;
        $this->setFormat($format);
        $this->setMargins(0, 0);
        $this->setFont(PdfFontName::ARIAL);
        $this->setAutoPageBreak(false);
        $this->setCellMargin(0);

        $col = 1 + $startIndex % $this->cols;
        if ($col > $this->cols) {
            throw PdfException::format('Invalid starting column: %d. Maximum allowed: %d.', $col, $this->cols);
        }
        $row = 1 + \intdiv($startIndex, $this->cols);
        if ($row > $this->rows) {
            throw PdfException::format('Invalid starting row: %d. Maximum allowed: %d.', $row, $this->rows);
        }
        $this->currentCol = $col - 1;
        $this->currentRow = $row - 1;
    }

    /**
     * Output a label.
     */
    public function addLabel(string $text): static
    {
        if (0 === $this->page) {
            $this->addPage();
        } elseif ($this->currentRow === $this->rows) {
            $this->currentRow = 0;
            $this->addPage();
        }
        $this->outputLabelText($text);
        $this->outputLabelBorder();
        if (++$this->currentCol === $this->cols) {
            $this->currentCol = 0;
            ++$this->currentRow;
        }

        return $this;
    }

    /**
     * This implementation skip output footer.
     */
    public function footer(): void
    {
    }

    /**
     * This implementation skip output header.
     */
    public function header(): void
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

    protected function putCatalog(): void
    {
        parent::putCatalog();
        $this->put('/ViewerPreferences <</PrintScaling /None>>');
    }

    /**
     * @psalm-param 'in'|'mm' $src
     */
    private function convertUnit(float $value, string $src): float
    {
        if ($src !== $this->unit) {
            return $value * self::UNIT_CONVERSION[$this->unit] / self::UNIT_CONVERSION[$src];
        }

        return $value;
    }

    /**
     * @psalm-return LabelType
     *
     * @throws PdfException if the format is invalid
     */
    private function getAveryFormat(string $format): array
    {
        $averyFormats = $this->getAveryFormats();
        if (isset($averyFormats[$format])) {
            return $averyFormats[$format];
        }

        $keys = \implode(', ', \array_keys($averyFormats));
        throw PdfException::format('Unknown label format: %s. Allowed formats: %s.', $format, $keys);
    }

    /**
     * @psalm-return array<string, LabelType>
     */
    private function getAveryFormats(): array
    {
        static $formats = null;
        if (null === $formats) {
            $file = __DIR__ . '/../../resources/data/avery.json';
            $formats = FileUtils::decodeJson($file);
        }

        /** @psalm-var array<string, LabelType> */
        return $formats;
    }

    /**
     * @psalm-param LabelType $format
     *
     * @throws PdfException if the page size is invalid
     */
    private function getDocumentSize(array $format): PdfPageSize
    {
        $value = \strtoupper($format['pageSize']);
        $size = PdfPageSize::tryFrom($value);
        if ($size instanceof PdfPageSize) {
            return $size;
        }

        throw PdfException::format('Invalid page size: %s.', $value);
    }

    /**
     * Give the line height for a given font size.
     *
     * @throws PdfException if the font size is invalid
     */
    private function getHeightChars(int $pt): float
    {
        if (!isset(self::FONT_CONVERSION[$pt])) {
            $sizes = \implode(', ', \array_keys(self::FONT_CONVERSION));
            throw PdfException::format('Invalid font size: %d. Allowed sizes: %s', $pt, $sizes);
        }

        return $this->convertUnit(self::FONT_CONVERSION[$pt], 'mm');
    }

    /**
     * Gets the horizontal label offset.
     */
    private function getLabelX(): float
    {
        return $this->getOffsetX() + $this->padding;
    }

    /**
     * Gets the vertical label offset.
     */
    private function getLabelY(string $text): float
    {
        $height = (float) $this->getLinesCount($text, $this->labelWidth - 2.0 * $this->padding) * $this->lineHeight;

        return $this->getOffsetY() + ($this->labelHeight - $height) / 2.0;
    }

    /**
     * Gets the horizontal offset.
     */
    private function getOffsetX(): float
    {
        return $this->marginLeft + (float) $this->currentCol * ($this->labelWidth + $this->spaceX);
    }

    /**
     * Gets the vertical offset.
     */
    private function getOffsetY(): float
    {
        return $this->marginTop + (float) $this->currentRow * ($this->labelHeight + $this->spaceY);
    }

    /**
     * Output border if applicable.
     */
    private function outputLabelBorder(): void
    {
        if (!$this->labelBorder) {
            return;
        }
        PdfDrawColor::cellBorder()->apply($this);
        $this->dashedRect($this->getOffsetX(), $this->getOffsetY(), $this->labelWidth, $this->labelHeight);
    }

    /**
     * Output label's text.
     */
    private function outputLabelText(string $text): void
    {
        if ('' === $text) {
            return;
        }

        $x = $this->getLabelX();
        $y = $this->getLabelY($text);
        $height = $this->lineHeight;
        $width = $this->labelWidth - $this->padding;

        // listener?
        if ($this->labelTextListener instanceof PdfLabelTextListenerInterface) {
            $texts = \explode(StringUtils::NEW_LINE, $text);
            $lines = \count($texts);
            foreach ($texts as $index => $value) {
                $this->setXY($x, $y);
                $event = new PdfLabelTextEvent($this, $value, $index, $lines, $width, $height);
                if (!$this->labelTextListener->drawLabelText($event)) {
                    $this->cell($width, $height, $value);
                }
                $y += $this->lastHeight;
            }

            return;
        }

        // default
        $this->setXY($x, $y);
        $this->multiCell(width: $width, height: $height, text: $text, align: PdfTextAlignment::LEFT);
    }

    /**
     * @psalm-param LabelType $format
     */
    private function setFormat(array $format): void
    {
        $unit = $format['unit'];
        $this->cols = $format['cols'];
        $this->rows = $format['rows'];
        $this->marginLeft = $this->convertUnit($format['marginLeft'], $unit);
        $this->marginTop = $this->convertUnit($format['marginTop'], $unit);
        $this->spaceX = $this->convertUnit($format['spaceX'], $unit);
        $this->spaceY = $this->convertUnit($format['spaceY'], $unit);
        $this->labelWidth = $this->convertUnit($format['width'], $unit);
        $this->labelHeight = $this->convertUnit($format['height'], $unit);
        $this->padding = $this->convertUnit(3, 'mm');
        $this->updateFontSize($format['fontSize']);
    }

    private function updateFontSize(int $pt): void
    {
        $this->lineHeight = $this->getHeightChars($pt);
        $this->setFontSizeInPoint($pt);
    }
}
