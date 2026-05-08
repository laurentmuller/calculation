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
use App\Pdf\Traits\PdfCleanTextTrait;
use App\Pdf\Traits\PdfStyleTrait;
use fpdf\Enums\PdfFontName;
use fpdf\Enums\PdfScaling;
use fpdf\PdfDocument;
use fpdf\PdfException;
use fpdf\Traits\PdfDashTrait;

/**
 * PDF document to output labels.
 **/
class PdfLabelDocument extends PdfDocument
{
    use PdfCleanTextTrait;
    use PdfDashTrait;
    use PdfStyleTrait;

    // the font mapping
    private const array FONT_CONVERSION = [
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
    private const float PADDING = 3.0;
    // the current column (0-based index)
    private int $currentCol;
    // the current row (0-based index)
    private int $currentRow;
    // the label format
    private PdfLabel $label;
    // the draw border around labels
    private bool $labelBorder = false;
    // the line height
    private float $lineHeight;

    /**
     * @param PdfLabel         $label      the label to output
     * @param non-negative-int $startIndex the zero-based index of the first label
     *
     * @throws PdfException if the label's font size is invalid
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

        $cols = $this->label->cols;
        $this->currentCol = $startIndex % $cols;
        $this->currentRow = \intdiv($startIndex % $this->label->size(), $cols);
    }

    /**
     * This implementation skips the output footer.
     */
    #[\Override]
    final public function footer(): void
    {
    }

    /**
     * This implementation skips the output header.
     */
    #[\Override]
    final public function header(): void
    {
    }

    /**
     * Output a label.
     *
     * Do nothing if the label is null or empty.
     *
     * @param PdfLabelItem|string|array<PdfLabelItem|string|null> $label the text, the label or an array of lines to output
     */
    public function outputLabel(string|PdfLabelItem|array $label): static
    {
        $items = $this->convertLabel($label);
        if ([] === $items) {
            return $this;
        }

        if (0 === $this->page) {
            $this->addPage();
        } elseif ($this->currentRow === $this->label->rows) {
            $this->currentRow = 0;
            $this->addPage();
        }

        $x = $this->getLabelX();
        $y = $this->getLabelY(\count($items));
        foreach ($items as $item) {
            $y = $this->outputLabelItem($x, $y, $item);
        }

        if ($this->labelBorder) {
            $this->outputLabelBorder();
        }
        if (++$this->currentCol === $this->label->cols) {
            $this->currentCol = 0;
            ++$this->currentRow;
        }

        return $this;
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
     * @param PdfLabelItem|string|array<PdfLabelItem|string|null> $label
     *
     * @return PdfLabelItem[]
     */
    private function convertLabel(string|PdfLabelItem|array $label): array
    {
        $items = [];
        foreach ((array) $label as $item) {
            if (null === $item) {
                continue;
            }
            $item = \is_string($item) ? new PdfLabelItem($item) : $item;
            if ($item->isText()) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Gets the horizontal label offset.
     */
    private function getLabelX(): float
    {
        return $this->label->offsetX($this->currentCol) + self::PADDING;
    }

    /**
     * Gets the vertical label offset.
     */
    private function getLabelY(int $rows): float
    {
        $height = (float) $rows * $this->lineHeight;

        return $this->label->offsetY($this->currentRow) + ($this->label->height - $height) / 2.0;
    }

    /**
     * Output the label's border if applicable.
     */
    private function outputLabelBorder(): void
    {
        PdfDrawColor::cellBorder()->apply($this);
        $x = $this->label->offsetX($this->currentCol);
        $y = $this->label->offsetY($this->currentRow);
        $this->dashedRect($x, $y, $this->label->width, $this->label->height, 1.0);
    }

    /**
     * Output the label item.
     */
    private function outputLabelItem(float $x, float $y, PdfLabelItem $item): float
    {
        $this->setXY($x, $y);
        $item->style?->apply($this);
        $height = $this->lineHeight;
        $width = $this->label->width - self::PADDING;
        $this->cell($width, $height, $item->text ?? '');
        $this->resetStyle();

        return $y + $this->lastHeight;
    }

    /**
     * Update font size and line height.
     */
    private function updateFont(int $pt): static
    {
        $this->lineHeight = self::FONT_CONVERSION[$pt]
            ?? throw PdfException::format('Invalid font size: %d. Allowed sizes: [%s].', $pt, \implode(', ', \array_keys(self::FONT_CONVERSION)));

        return $this->setFontSizeInPoint($pt)
            ->setFont(PdfFontName::ARIAL);
    }
}
