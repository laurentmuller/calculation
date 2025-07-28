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

use App\Pdf\Events\PdfCellBackgroundEvent;
use App\Pdf\Events\PdfCellBorderEvent;
use App\Pdf\Events\PdfCellTextEvent;
use App\Pdf\Events\PdfPdfDrawHeadersEvent;
use App\Pdf\Interfaces\PdfDrawCellBackgroundInterface;
use App\Pdf\Interfaces\PdfDrawCellBorderInterface;
use App\Pdf\Interfaces\PdfDrawCellTextInterface;
use App\Pdf\Interfaces\PdfDrawHeadersInterface;
use App\Traits\MathTrait;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfBorder;
use fpdf\PdfDocument;
use fpdf\PdfException;
use fpdf\PdfRectangle;

/**
 * Class to build and output a table.
 *
 * @see PdfColumn
 */
class PdfTable
{
    use MathTrait;

    /**
     * The table alignment when not is full width.
     */
    private PdfTextAlignment $alignment = PdfTextAlignment::LEFT;

    /**
     * The draw cell background listener.
     */
    private ?PdfDrawCellBackgroundInterface $backgroundListener = null;

    /**
     * The default cell border.
     */
    private PdfBorder $border;

    /**
     * The draw cell border listener.
     */
    private ?PdfDrawCellBorderInterface $borderListener = null;

    /**
     * The cells.
     *
     * @var PdfCell[]
     */
    private array $cells = [];

    /**
     * The default cell style.
     */
    private ?PdfStyle $cellStyle = null;

    /**
     * The columns.
     *
     * @var PdfColumn[]
     */
    private array $columns = [];

    /**
     * The draw headers listener.
     */
    private ?PdfDrawHeadersInterface $headersListener = null;

    /**
     * The default header style.
     */
    private ?PdfStyle $headerStyle = null;

    /**
     * The output headers state.
     */
    private bool $isHeaders = false;

    /**
     * Print headers when a new page is added.
     */
    private bool $repeatHeader = true;

    /**
     * The current row style.
     */
    private ?PdfStyle $rowStyle = null;

    /**
     * The draw cell text listener.
     */
    private ?PdfDrawCellTextInterface $textListener = null;

    /**
     * @param PdfDocument $parent    the parent document to print in
     * @param bool        $fullWidth a value indicating if the table takes all the printable width
     */
    public function __construct(private readonly PdfDocument $parent, private readonly bool $fullWidth = true)
    {
        $this->border = PdfBorder::all();
    }

    /**
     * Adds a cell to the current row.
     *
     * @param ?string           $text      the text of the cell
     * @param positive-int      $cols      the number of columns to span
     * @param ?PdfStyle         $style     the cell style or null to use the default cell style
     * @param ?PdfTextAlignment $alignment the text alignment or null to use the column alignment
     * @param string|int|null   $link      the optional cell link.
     *                                     A URL or identifier returned by the <code>addLink()</code> function.
     *
     * @throws PdfException if no current row is started
     */
    public function add(
        ?string $text = null,
        int $cols = 1,
        ?PdfStyle $style = null,
        ?PdfTextAlignment $alignment = null,
        string|int|null $link = null
    ): static {
        return $this->addCell(new PdfCell($text, $cols, $style, $alignment, $link));
    }

    /**
     * Adds the given cell to the list of cells.
     *
     * @throws PdfException if no current row is started
     */
    public function addCell(PdfCell $cell): static
    {
        if (!$this->isRowStarted()) {
            throw PdfException::instance('No row started.');
        }
        $this->cells[] = $cell;

        return $this;
    }

    /**
     * Adds the given column to the list of columns.
     *
     * @see PdfTable::addColumns()
     */
    public function addColumn(PdfColumn $column): static
    {
        $this->columns[] = $column;

        return $this;
    }

    /**
     * Adds the given columns to the list of columns.
     *
     * @see PdfTable::addColumn()
     */
    public function addColumns(PdfColumn ...$columns): static
    {
        $this->columns = \array_merge($this->columns, $columns);

        return $this;
    }

    /**
     * Create and add a header row with the given values.
     *
     * @throws PdfException if the row is already started, if values are empty, or if the number of values is greater
     *                      than the number of columns
     */
    public function addHeaderRow(PdfCell|string|null ...$values): static
    {
        $this->startHeaderRow()
            ->addValues(...$values)
            ->completeRow();

        return $this;
    }

    /**
     * Create and add a row with the given values.
     *
     * @throws PdfException if the row is already started, if values are empty, or if the number of values is greater
     *                      than the number of columns
     *
     * @see PdfTable::addStyledRow()
     */
    public function addRow(PdfCell|string|null ...$values): static
    {
        return $this->startRow()
            ->addValues(...$values)
            ->completeRow();
    }

    /**
     * Output a row with an optional style.
     *
     * @param array<PdfCell|string|null> $cells the cells to output
     * @param ?PdfStyle                  $style the row style or null for default cell style
     *
     * @throws PdfException if a row is already started, if the cells are empty, or if the number of spanned cells is
     *                      greater than the number of columns
     *
     * @see PdfTable::addRow()
     */
    public function addStyledRow(array $cells, ?PdfStyle $style = null): static
    {
        return $this->startRow($style)
            ->addValues(...$cells)
            ->completeRow();
    }

    /**
     * Adds the given values to the list of cells.
     *
     * @throws PdfException if no current row is started
     */
    public function addValues(PdfCell|string|null ...$values): static
    {
        foreach ($values as $value) {
            if ($value instanceof PdfCell) {
                $this->addCell($value);
            } else {
                $this->add($value);
            }
        }

        return $this;
    }

    /**
     * Adds a new page, if needed, for the given height.
     *
     * @param float $height The desired height
     *
     * @return bool true if a new page is added
     *
     * @see PdfDocument::isPrintable()
     */
    public function checkNewPage(float $height): bool
    {
        if ($this->parent->isPrintable($height)) {
            return false;
        }

        $this->parent->addPage();
        if (!$this->isHeaders && $this->isRepeatHeader()) {
            $this->outputHeaders();
        }

        return true;
    }

    /**
     * Completes the current row with empty cells.
     *
     * @throws PdfException if no current row is started
     */
    public function completeRow(): static
    {
        if (!$this->isRowStarted()) {
            throw PdfException::instance('No row started.');
        }
        $remaining = $this->getColumnsCount() - $this->getCellsSpan();
        for ($i = 0; $i < $remaining; ++$i) {
            $this->add('');
        }

        return $this->endRow();
    }

    /**
     * Output the current row.
     *
     * After this call, no more cell is defined.
     *
     * @throws PdfException if no cell is defined or, if the number of spanned cells is not equal to the number of
     *                      columns
     */
    public function endRow(): static
    {
        // check
        if ([] === $this->cells) {
            throw PdfException::instance('No cell defined.');
        }
        $span = $this->getCellsSpan();
        $count = $this->getColumnsCount();
        if ($span !== $count) {
            throw PdfException::format('Invalid spanned cells: expected %d, %d given.', $count, $span);
        }

        // compute
        $cells = $this->cells;
        $columns = $this->columns;
        [$texts, $styles, $aligns, $widths, $fixeds] = $this->computeCells($cells, $columns);

        // update widths
        if ($this->fullWidth) {
            $this->adjustCellWidths($cells, $fixeds, $widths);
        }

        // clear before adding a new page
        $this->cells = [];
        $this->rowStyle = null;

        // check the new page
        $height = $this->getRowHeight($texts, $widths, $styles, $cells);
        $this->checkNewPage($height);

        // output
        $this->drawRow($this->parent, $height, $texts, $widths, $styles, $aligns, $cells);
        $this->isHeaders = false;

        return $this;
    }

    /**
     * Gets the default cell border.
     *
     * By default, all borders are drawn.
     */
    public function getBorder(): PdfBorder
    {
        return $this->border;
    }

    /**
     * Gets the default cell style.
     *
     * @return PdfStyle the cell style, if set, the default style otherwise
     *
     * @see PdfStyle::getCellStyle()
     */
    public function getCellStyle(): PdfStyle
    {
        return $this->cellStyle ?? PdfStyle::getCellStyle();
    }

    /**
     * Gets the columns.
     *
     * @return PdfColumn[] the columns
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Gets the number of columns.
     */
    public function getColumnsCount(): int
    {
        return \count($this->columns);
    }

    /**
     * Gets the default header style.
     *
     * @return PdfStyle the header style, if set, the default header style otherwise
     *
     * @see PdfStyle::getHeaderStyle()
     */
    public function getHeaderStyle(): PdfStyle
    {
        return $this->headerStyle ?? PdfStyle::getHeaderStyle();
    }

    /**
     * Gets the parent.
     */
    public function getParent(): PdfDocument
    {
        return $this->parent;
    }

    /**
     * Creates a new instance.
     *
     * @param PdfDocument $parent    the parent document to print in
     * @param bool        $fullWidth a value indicating if the table takes all the printable width
     */
    public static function instance(PdfDocument $parent, bool $fullWidth = true): self
    {
        return new self($parent, $fullWidth);
    }

    /**
     * Gets a value indicating if the table takes all the printable width.
     */
    public function isFullWidth(): bool
    {
        return $this->fullWidth;
    }

    /**
     * Returns a value indicating if a header row is drawing.
     *
     * @return bool <code>true</code> if a header row is drawing; <code>false</code> otherwise
     */
    public function isHeaders(): bool
    {
        return $this->isHeaders;
    }

    /**
     * Returns if the header row is printed when a new page is added.
     */
    public function isRepeatHeader(): bool
    {
        return $this->repeatHeader;
    }

    /**
     * Returns a value indicating if a row is started.
     */
    public function isRowStarted(): bool
    {
        return $this->rowStyle instanceof PdfStyle;
    }

    /**
     * Output a row with the header style and the column's texts.
     *
     * @throws PdfException if no column is defined
     */
    public function outputHeaders(): static
    {
        if ([] === $this->columns) {
            throw PdfException::instance('No column defined.');
        }

        if ($this->headersListener instanceof PdfDrawHeadersInterface) {
            $event = new PdfPdfDrawHeadersEvent($this, $this->getHeaderStyle());
            $this->isHeaders = true;
            $result = $this->headersListener->drawHeaders($event);
            $this->isHeaders = false;
            if ($result) {
                return $this;
            }
        }

        return $this->addHeaderRow(...\array_map(static fn (PdfColumn $c): ?string => $c->getText(), $this->columns));
    }

    /**
     * Sets the alignment.
     *
     * This value is used only when the full width property is false
     *
     * @see PdfTable::isFullWidth()
     */
    public function setAlignment(PdfTextAlignment $alignment): static
    {
        $this->alignment = $alignment;

        return $this;
    }

    /**
     * Sets the draw cell background listener.
     */
    public function setBackgroundListener(?PdfDrawCellBackgroundInterface $backgroundListener): static
    {
        $this->backgroundListener = $backgroundListener;

        return $this;
    }

    /**
     * Sets the default cell border.
     */
    public function setBorder(PdfBorder $border): static
    {
        $this->border = $border;

        return $this;
    }

    /**
     * Sets the draw cell border listener.
     */
    public function setBorderListener(?PdfDrawCellBorderInterface $borderListener): static
    {
        $this->borderListener = $borderListener;

        return $this;
    }

    /**
     * Sets the default cell style.
     *
     * @param ?PdfStyle $cellStyle the style to set or null to use the default cell style
     *
     * @see PdfStyle::getCellStyle()
     */
    public function setCellStyle(?PdfStyle $cellStyle): static
    {
        $this->cellStyle = $cellStyle;

        return $this;
    }

    /**
     * Sets the draw header listener.
     */
    public function setHeadersListener(?PdfDrawHeadersInterface $headersListener): static
    {
        $this->headersListener = $headersListener;

        return $this;
    }

    /**
     * Sets the default header style.
     *
     * @param ?PdfStyle $headerStyle the style to set or null to use the default header style
     *
     * @see PdfStyle::getHeaderStyle()
     */
    public function setHeaderStyle(?PdfStyle $headerStyle = null): static
    {
        $this->headerStyle = $headerStyle;

        return $this;
    }

    /**
     * Sets a value indicating if the header row is printed when a new page is added.
     *
     * @param bool $repeatHeader true to print the header on each new page
     */
    public function setRepeatHeader(bool $repeatHeader): static
    {
        $this->repeatHeader = $repeatHeader;

        return $this;
    }

    /**
     * Sets the draw cell text listener.
     */
    public function setTextListener(?PdfDrawCellTextInterface $textListener): static
    {
        $this->textListener = $textListener;

        return $this;
    }

    /**
     * Output a row with a single cell.
     *
     * @param ?string           $text      the text of the cell
     * @param ?PdfStyle         $style     the row style to use or null to use the default cell style
     * @param ?PdfTextAlignment $alignment the cell alignment
     *
     * @throws PdfException if a row is already started or if no column is defined
     *
     * @see PdfTable::add()
     */
    public function singleLine(
        ?string $text = null,
        ?PdfStyle $style = null,
        ?PdfTextAlignment $alignment = null
    ): static {
        /** @phpstan-var positive-int $cols */
        $cols = $this->getColumnsCount();

        return $this->startRow()
            ->add($text, $cols, $style, $alignment)
            ->endRow();
    }

    /**
     * Starts a new header row with the default header style.
     *
     * @throws PdfException if a row is already started
     *
     * @see PdfTable::getHeaderStyle()
     */
    public function startHeaderRow(): static
    {
        if ($this->isRowStarted()) {
            throw PdfException::instance('Row already started.');
        }
        $this->isHeaders = true;

        return $this->startRow($this->getHeaderStyle());
    }

    /**
     * Starts a new row with the given optional style.
     *
     * @param ?PdfStyle $style the row style to use or null to use the default cell style
     *
     * @throws PdfException if the row is already started
     *
     * @see PdfTable::getCellStyle()
     */
    public function startRow(?PdfStyle $style = null): static
    {
        if ($this->isRowStarted()) {
            throw PdfException::instance('Row already started.');
        }
        $this->rowStyle = $style ?? $this->getCellStyle();

        return $this;
    }

    /**
     * @param PdfCell[] $cells
     * @param bool[]    $fixeds
     * @param float[]   $widths
     */
    private function adjustCellWidths(array $cells, array $fixeds, array &$widths): void
    {
        $count = \count($cells);
        $printableWidth = $this->parent->getPrintableWidth();

        // only 1 cell?
        if (1 === $count) {
            $widths[0] = $printableWidth;

            return;
        }

        // compute widths and check values
        [$resizableWidth, $fixedWidth] = $this->computeCellWidths($fixeds, $widths);
        $remainingWidth = $printableWidth - $fixedWidth;
        if ($this->isFloatZero($resizableWidth) || $this->isFloatZero($remainingWidth)
            || $this->isFloatEquals($resizableWidth, $remainingWidth)) {
            return;
        }

        // update resizable widths
        $factor = $remainingWidth / $resizableWidth;
        for ($i = 0; $i < $count; ++$i) {
            if (!$fixeds[$i]) {
                $widths[$i] *= $factor;
            }
        }
    }

    /**
     * @param PdfCell[]   $cells
     * @param PdfColumn[] $columns
     *
     * @phpstan-return array{
     *     0: string[],
     *     1: PdfStyle[],
     *     2: PdfTextAlignment[],
     *     3: float[],
     *     4: bool[]}
     *
     * @psalm-suppress PossiblyNullReference
     */
    private function computeCells(array $cells, array $columns): array
    {
        $texts = [];
        $styles = [];
        $aligns = [];
        $widths = [];
        $fixeds = [];

        $index = 0;
        foreach ($cells as $cell) {
            $texts[] = $cell->getText() ?? '';
            $styles[] = $cell->getStyle() ?? $this->rowStyle ?? $this->getCellStyle();
            $aligns[] = $cell->getAlignment() ?? $columns[$index]->getAlignment() ?? PdfTextAlignment::LEFT;

            $width = 0.0;
            $fixed = $columns[$index]->isFixed();
            for ($i = 0, $count = $cell->getCols(); $i < $count; ++$i) {
                if ($fixed && !$columns[$index]->isFixed()) {
                    $fixed = false;
                }
                $width += $columns[$index]->getWidth();
                ++$index;
            }
            $widths[] = $width;
            $fixeds[] = $fixed;
        }

        return [
            $texts,
            $styles,
            $aligns,
            $widths,
            $fixeds,
        ];
    }

    /**
     * Get the sum of resizable and fixed widths.
     *
     * @param bool[]  $fixeds
     * @param float[] $widths
     *
     * @return array{0: float, 1: float}
     */
    private function computeCellWidths(array $fixeds, array $widths): array
    {
        $result = [0.0, 0.0];
        foreach ($fixeds as $index => $fixed) {
            $result[(int) $fixed] += $widths[$index];
        }

        return $result;
    }

    /**
     * Output a single cell.
     *
     * After this call, the current position is at the top/right of the cell.
     *
     * @param PdfDocument      $parent    the parent document
     * @param int              $index     the column index
     * @param float            $width     the cell width
     * @param float            $height    the cell height
     * @param string           $text      the cell text
     * @param PdfTextAlignment $alignment the cell text alignment
     * @param PdfStyle         $style     the cell style
     * @param PdfCell          $cell      the cell
     */
    private function drawCell(
        PdfDocument $parent,
        int $index,
        float $width,
        float $height,
        string $text,
        PdfTextAlignment $alignment,
        PdfStyle $style,
        PdfCell $cell
    ): void {
        $position = $parent->getPosition();
        $bounds = new PdfRectangle($position->x, $position->y, $width, $height);

        // background
        $style->apply($parent);
        $this->drawCellBackground($parent, $index, $bounds, $style);

        // border
        $style->apply($parent);
        $parent->setPosition($position);
        $border = $style->getBorder() ?? $this->border;
        $this->drawCellBorder($parent, $index, $bounds, $border);

        // image and/or text
        $style->apply($parent);
        $parent->setPosition($position);
        $margin = $parent->getCellMargin();
        $textBounds = clone $bounds;
        $line_height = PdfDocument::LINE_HEIGHT;

        if ($cell instanceof AbstractPdfImageCell) {
            $cell->output($parent, clone $textBounds, $alignment);
        } else {
            if (!$style->getFont()->isDefaultSize()) {
                $line_height = $parent->getFontSize() + 2.0 * $margin;
            }
            $indent = $style->getIndent();
            if ($indent > 0) {
                $parent->setX($position->x + $indent);
                $textBounds->indent($indent);
            }
            $this->drawCellText($parent, $index, $textBounds, $text, $alignment, $line_height);
        }

        if ($cell->hasLink()) {
            /** @phpstan-var string|int $link */
            $link = $cell->getLink();
            $linkBounds = (clone $textBounds)->inflate(-$margin);
            $linesCount = \max(1, $parent->getLinesCount($text, $linkBounds->width));
            $linkBounds->width = \min($linkBounds->width, $parent->getStringWidth($text));
            $linkBounds->height = \min($linkBounds->height, (float) $linesCount * $line_height - 2.0 * $margin);
            $parent->link($linkBounds->x, $linkBounds->y, $linkBounds->width, $linkBounds->height, $link);
        }

        $position->x += $width;
        $parent->setPosition($position);
    }

    /**
     * Draws the cell background.
     *
     * @param PdfDocument  $parent the parent document
     * @param int          $index  the column index
     * @param PdfRectangle $bounds the cell bounds
     * @param PdfStyle     $style  the cell style
     */
    private function drawCellBackground(PdfDocument $parent, int $index, PdfRectangle $bounds, PdfStyle $style): void
    {
        if ($this->backgroundListener instanceof PdfDrawCellBackgroundInterface) {
            $event = new PdfCellBackgroundEvent($this, $index, clone $bounds);
            if ($this->backgroundListener->drawCellBackground($event)) {
                return;
            }
        }
        if ($style->isFillColor()) {
            $parent->rectangle($bounds, PdfRectangleStyle::FILL);
        }
    }

    /**
     * Draws the cell border.
     *
     * @param PdfDocument  $parent the parent document
     * @param int          $index  the column index
     * @param PdfRectangle $bounds the cell bounds
     * @param PdfBorder    $border the border style
     */
    private function drawCellBorder(PdfDocument $parent, int $index, PdfRectangle $bounds, PdfBorder $border): void
    {
        if ($this->borderListener instanceof PdfDrawCellBorderInterface) {
            $event = new PdfCellBorderEvent($this, $index, clone $bounds, clone $border);
            if ($this->borderListener->drawCellBorder($event)) {
                return;
            }
        }

        if (!$border->isNone()) {
            $border->draw($parent, $bounds);
        }
    }

    /**
     * Draws the cell text.
     *
     * @param PdfDocument      $parent    the parent document
     * @param int              $index     the column index
     * @param PdfRectangle     $bounds    the cell bounds
     * @param string           $text      the cell text
     * @param PdfTextAlignment $alignment the text alignment
     * @param float            $height    the line height
     */
    private function drawCellText(
        PdfDocument $parent,
        int $index,
        PdfRectangle $bounds,
        string $text,
        PdfTextAlignment $alignment,
        float $height
    ): void {
        if ($this->textListener instanceof PdfDrawCellTextInterface) {
            $event = new PdfCellTextEvent($this, $index, clone $bounds, $text, $alignment, $height);
            if ($this->textListener->drawCellText($event)) {
                return;
            }
        }
        if ('' !== $text) {
            $parent->multiCell(width: $bounds->width, height: $height, text: $text, align: $alignment);
        }
    }

    /**
     * Output a row.
     *
     * @param PdfDocument        $parent the parent document
     * @param float              $height the row height
     * @param string[]           $texts  the cell texts
     * @param float[]            $widths the cell widths
     * @param PdfStyle[]         $styles the cell styles
     * @param PdfTextAlignment[] $aligns the cell alignments
     * @param PdfCell[]          $cells  the cells
     */
    private function drawRow(
        PdfDocument $parent,
        float $height,
        array $texts,
        array $widths,
        array $styles,
        array $aligns,
        array $cells
    ): void {
        // horizontal alignment
        if (!$this->fullWidth) {
            switch ($this->alignment) {
                case PdfTextAlignment::CENTER:
                case PdfTextAlignment::JUSTIFIED:
                    $w = (float) \array_sum($widths);
                    $x = $parent->getLeftMargin() + ($parent->getPrintableWidth() - $w) / 2.0;
                    $parent->setX($x);
                    break;
                case PdfTextAlignment::RIGHT:
                    $w = (float) \array_sum($widths);
                    $x = $parent->getPageWidth() - $parent->getRightMargin() - $w;
                    $parent->setX($x);
                    break;
                default:
                    break;
            }
        }

        // output cells
        for ($i = 0, $len = \count($texts); $i < $len; ++$i) {
            $this->drawCell($parent, $i, $widths[$i], $height, $texts[$i], $aligns[$i], $styles[$i], $cells[$i]);
        }

        // next line
        $parent->lineBreak($height);
    }

    /**
     * Gets the cell height.
     *
     * @param ?string  $text  the cell text
     * @param float    $width the cell width
     * @param PdfStyle $style the cell style
     * @param PdfCell  $cell  the cell
     *
     * @return float the cell height
     */
    private function getCellHeight(?string $text, float $width, PdfStyle $style, PdfCell $cell): float
    {
        $imageHeight = 0.0;
        $parent = $this->parent;
        $margins = 2.0 * $parent->getCellMargin();
        if ($cell instanceof AbstractPdfImageCell) {
            $imageHeight = $margins + $parent->pixels2UserUnit($cell->getHeight());
            $width -= $parent->pixels2UserUnit($cell->getWidth());
        }

        $style->apply($parent);
        $width = \max(0, $width - $style->getIndent());
        $lines = (float) $parent->getLinesCount($text, $width);
        $height = PdfDocument::LINE_HEIGHT;
        if (!$style->getFont()->isDefaultSize()) {
            $fontSize = $style->getFont()->getSize();
            $height = $parent->points2UserUnit($fontSize) + $margins;
        }

        return \max($imageHeight, $lines * $height);
    }

    /**
     * Gets the total columns span.
     *
     * @see PdfCell::getCols()
     */
    private function getCellsSpan(): int
    {
        return \array_reduce($this->cells, static fn (int $carry, PdfCell $cell): int => $carry + $cell->getCols(), 0);
    }

    /**
     * Gets the row height.
     *
     * @param string[]   $texts  the cell texts
     * @param float[]    $widths the cell widths
     * @param PdfStyle[] $styles the cell styles
     * @param PdfCell[]  $cells  the cells
     */
    private function getRowHeight(array $texts, array $widths, array $styles, array $cells): float
    {
        $height = 0.0;
        foreach ($texts as $index => $text) {
            $height = \max($height, $this->getCellHeight($text, $widths[$index], $styles[$index], $cells[$index]));
        }

        return $height;
    }
}
