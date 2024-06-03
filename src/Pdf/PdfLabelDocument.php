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
use App\Utils\StringUtils;
use fpdf\PdfException;
use fpdf\PdfFontName;
use fpdf\PdfScaling;
use fpdf\PdfTextAlignment;

/**
 * PDF document to output labels.
 **/
class PdfLabelDocument extends PdfDocument
{
    use PdfDashLineTrait;

    // the font mapping
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

    // the padding inside labels
    private const PADDING = 3.0;

    // the current column (0 based index)
    private int $currentCol;
    // the current row (0 based index)
    private int $currentRow;
    // the Avery format
    private PdfLabel $label;
    // the draw border around labels
    private bool $labelBorder = false;
    // the label listener
    private ?PdfLabelTextListenerInterface $labelTextListener = null;
    // the line height
    private float $lineHeight;

    /**
     * @param PdfLabel $label      the label to output
     * @param int      $startIndex the zero-based index of the first label
     *
     * @throws PdfException if the label's font size is invalid
     *
     * @psalm-param non-negative-int $startIndex
     */
    public function __construct(PdfLabel $label, int $startIndex = 0)
    {
        parent::__construct(size: $label->pageSize);

        $this->getViewerPreferences()
            ->setScaling(PdfScaling::NONE);

        $this->label = $label->scaleToMillimeters();
        $this->updateFont($label->fontSize)
            ->setAutoPageBreak(false)
            ->setMargins(0, 0)
            ->setCellMargin(0);

        $this->currentCol = $startIndex % $this->label->cols;
        $this->currentRow = \intdiv($startIndex % $this->label->size(), $this->label->cols);
    }

    /**
     * Output a label.
     */
    public function addLabel(string $text): static
    {
        if (0 === $this->page) {
            $this->addPage();
        } elseif ($this->currentRow === $this->label->rows) {
            $this->currentRow = 0;
            $this->addPage();
        }
        $this->outputLabelText($text);
        $this->outputLabelBorder();
        if (++$this->currentCol === $this->label->cols) {
            $this->currentCol = 0;
            ++$this->currentRow;
        }

        return $this;
    }

    /**
     * This implementation skip output footer.
     */
    final public function footer(): void
    {
    }

    /**
     * This implementation skip output header.
     */
    final public function header(): void
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
     * Sets the draw label listener.
     */
    public function setLabelTextListener(?PdfLabelTextListenerInterface $labelTextListener): static
    {
        $this->labelTextListener = $labelTextListener;

        return $this;
    }

    /**
     * Gets the horizontal label offset.
     */
    private function getLabelX(): float
    {
        return $this->label->getOffsetX($this->currentCol) + self::PADDING;
    }

    /**
     * Gets the vertical label offset.
     */
    private function getLabelY(string $text): float
    {
        $y = $this->label->getOffsetY($this->currentRow);
        $lines = \count(\explode(StringUtils::NEW_LINE, $text));
        $height = (float) $lines * $this->lineHeight;

        return $y + ($this->label->height - $height) / 2.0;
    }

    /**
     * Output the label's border if applicable.
     */
    private function outputLabelBorder(): void
    {
        if (!$this->labelBorder) {
            return;
        }

        PdfDrawColor::cellBorder()->apply($this);
        $x = $this->label->getOffsetX($this->currentCol);
        $y = $this->label->getOffsetY($this->currentRow);
        $this->dashedRect($x, $y, $this->label->width, $this->label->height);
    }

    /**
     * Output the label's text if applicable.
     */
    private function outputLabelText(string $text): void
    {
        if ('' === $text) {
            return;
        }

        $x = $this->getLabelX();
        $y = $this->getLabelY($text);
        $height = $this->lineHeight;
        $width = $this->label->width - self::PADDING;
        if (!$this->labelTextListener instanceof PdfLabelTextListenerInterface) {
            $this->setXY($x, $y);
            $this->multiCell(width: $width, height: $height, text: $text, align: PdfTextAlignment::LEFT);

            return;
        }

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
    }

    /**
     * Update font size and line height.
     */
    private function updateFont(int $pt): static
    {
        if (!isset(self::FONT_CONVERSION[$pt])) {
            $sizes = \implode(', ', \array_keys(self::FONT_CONVERSION));
            throw PdfException::format('Invalid font size: %d. Allowed sizes: [%s]', $pt, $sizes);
        }
        $this->lineHeight = self::FONT_CONVERSION[$pt];

        return $this->setFontSizeInPoint($pt)
            ->setFont(PdfFontName::ARIAL);
    }
}
