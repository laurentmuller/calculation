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
use App\Utils\StringUtils;
use fpdf\PdfBorder;
use fpdf\PdfRectangleStyle;
use fpdf\PdfTextAlignment;

/**
 * Class to build and output a table.
 *
 * @see PdfColumn
 */
class PdfTable
{
    use MathTrait;

    /**
     * The column alignment.
     */
    private PdfTextAlignment $alignment = PdfTextAlignment::LEFT;

    /**
     * The draw cell background listener.
     */
    private ?PdfDrawCellBackgroundInterface $backgroundListener = null;

    /**
     * The border style.
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
     * The header style.
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
     * @param bool        $fullWidth a value indicating if the table take all the printable width
     */
    public function __construct(private readonly PdfDocument $parent, private readonly bool $fullWidth = true)
    {
        $this->border = PdfBorder::all();
    }

    /**
     * Adds a cell to the current row.
     *
     * @param ?string           $text      the text of the cell
     * @param int               $cols      the number of columns to span
     * @param ?PdfStyle         $style     the cell style to use or null to use the default cell style
     * @param ?PdfTextAlignment $alignment the cell alignment
     * @param string|int        $link      the cell link. A URL or identifier returned by AddLink().
     *
     * @psalm-param positive-int $cols
     */
    public function add(?string $text = null, int $cols = 1, ?PdfStyle $style = null, ?PdfTextAlignment $alignment = null, string|int $link = ''): static
    {
        return $this->addCell(new PdfCell($text, $cols, $style, $alignment, $link));
    }

    /**
     * Adds the given cell to the list of cells.
     *
     * Do nothing if the cell is null.
     *
     * @throws \LogicException if no current row is started
     */
    public function addCell(?PdfCell $cell): static
    {
        if (!$this->isRowStarted()) {
            throw new \LogicException('No row started.');
        }
        if ($cell instanceof PdfCell) {
            $this->cells[] = $cell;
        }

        return $this;
    }

    /**
     * Adds the given column to the list of columns.
     *
     * Do nothing if the column is null.
     *
     * @see PdfTable::addColumns()
     */
    public function addColumn(?PdfColumn $column): static
    {
        if ($column instanceof PdfColumn) {
            $this->columns[] = $column;
        }

        return $this;
    }

    /**
     * Adds the given columns to the list of columns.
     *
     * The null columns are not added.
     *
     * @see PdfTable::addColumn()
     */
    public function addColumns(PdfColumn ...$columns): static
    {
        foreach ($columns as $column) {
            $this->addColumn($column);
        }

        return $this;
    }

    /**
     * Create and add a header row with the given values.
     *
     * @throws \LogicException      if the row is already started
     * @throws \LengthException     if values parameter is empty
     * @throws \OutOfRangeException if the number of spanned cells is greater than the number of columns
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
     * @throws \LogicException      if the row is already started
     * @throws \LengthException     if values parameter is empty
     * @throws \OutOfRangeException if the number of spanned cells is greater than the number of columns
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
     * @throws \LogicException      if a row is already started
     * @throws \LengthException     if cells parameter is empty
     * @throws \OutOfRangeException if the number of spanned cells is greater than the number of columns
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
     * @throws \LogicException if no current row is started
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
     * @param bool $endRow true to ending the row after completed
     *
     * @throws \LogicException if no current row is started
     */
    public function completeRow(bool $endRow = true): static
    {
        if (!$this->isRowStarted()) {
            throw new \LogicException('No row started.');
        }
        $remaining = $this->getColumnsCount() - $this->getCellsSpan();
        for ($i = 0; $i < $remaining; ++$i) {
            $this->add('');
        }

        return $endRow ? $this->endRow() : $this;
    }

    /**
     * Output the current row.
     *
     * After this call, no more cell is defined.
     *
     * @throws \LengthException     if no cell is defined
     * @throws \OutOfRangeException if the number of spanned cells is not equal to the number of columns
     */
    public function endRow(): static
    {
        // check
        if ([] === $this->cells) {
            throw new \LengthException('No cell defined.');
        }
        $span = $this->getCellsSpan();
        $count = $this->getColumnsCount();
        if ($span !== $count) {
            throw new \OutOfRangeException(\sprintf('Invalid spanned cells: expected %d, %d given.', $count, $span));
        }

        $cells = $this->cells;
        $parent = $this->parent;
        $columns = $this->columns;

        // compute
        [$texts, $styles, $aligns, $widths, $fixeds] = $this->computeCells($cells, $columns);

        // update widths
        if ($this->fullWidth) {
            $this->adjustCellWidths($cells, $fixeds, $widths);
        }

        // clear before adding new page
        $this->cells = [];
        $this->rowStyle = null;

        // check new page
        $height = $this->getRowHeight($texts, $widths, $styles, $cells);
        $this->checkNewPage($height);

        // output
        $this->drawRow($parent, $height, $texts, $widths, $styles, $aligns, $cells);
        $this->isHeaders = false;

        return $this;
    }

    /**
     * Gets the border.
     */
    public function getBorder(): PdfBorder
    {
        return $this->border;
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
     * Gets the header style.
     *
     * @return PdfStyle the custom header style, if set; the default header style otherwise
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
     * @param bool        $fullWidth a value indicating if the table take all the printable width
     */
    public static function instance(PdfDocument $parent, bool $fullWidth = true): self
    {
        return new self($parent, $fullWidth);
    }

    /**
     * Gets a value indicating if the table take all the printable width.
     */
    public function isFullWidth(): bool
    {
        return $this->fullWidth;
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
     * Output a row with the header style and the columns texts.
     *
     * @throws \LengthException if no column is defined
     */
    public function outputHeaders(): static
    {
        if ([] === $this->columns) {
            throw new \LengthException('No column defined.');
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

        return $this->addHeaderRow(...\array_map(fn (PdfColumn $c): ?string => $c->getText(), $this->columns));
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
     * Sets the border.
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
     * Sets the draw header listener.
     */
    public function setHeadersListener(?PdfDrawHeadersInterface $headersListener): static
    {
        $this->headersListener = $headersListener;

        return $this;
    }

    /**
     * Sets a value indicating if the header row is printed when a new page is added.
     *
     * @param bool $repeatHeader true to print the header on each new pages
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
     * @throws \LogicException  if a row is already started
     * @throws \LengthException if no column is defined
     *
     * @see PdfTable::add()
     */
    public function singleLine(?string $text = null, ?PdfStyle $style = null, ?PdfTextAlignment $alignment = null): static
    {
        /** @psalm-var positive-int $cols */
        $cols = $this->getColumnsCount();

        return $this->startRow()
            ->add($text, $cols, $style, $alignment)
            ->endRow();
    }

    /**
     * Starts a new row with the custom header style, if set; with the default header style otherwise.
     *
     * @throws \LogicException if a row is already started
     *
     * @see PdfStyle::getHeaderStyle()
     * @see PdfTable::getHeaderStyle()
     */
    public function startHeaderRow(): static
    {
        $this->checkRowStarted();
        $this->isHeaders = true;

        return $this->startRow($this->getHeaderStyle());
    }

    /**
     * Starts a new row.
     *
     * @param ?PdfStyle $style the row style to use or null to use the default cell style
     *
     * @throws \LogicException if the row is already started
     */
    public function startRow(?PdfStyle $style = null): static
    {
        $this->checkRowStarted();
        $this->rowStyle = $style ?? PdfStyle::getCellStyle();

        return $this;
    }

    /**
     * Draws the cell link.
     *
     * @param PdfDocument  $parent the parent document
     * @param PdfRectangle $bounds the link bounds
     * @param string|int   $link   the link URL
     */
    protected function drawCellLink(PdfDocument $parent, PdfRectangle $bounds, string|int $link): void
    {
        $parent->link($bounds->x(), $bounds->y(), $bounds->width(), $bounds->height(), $link);
    }

    /**
     * Output a row.
     *
     * @param PdfDocument        $parent the parent document
     * @param float              $height the row height
     * @param string[]           $texts  the cells text
     * @param float[]            $widths the cells width
     * @param PdfStyle[]         $styles the cells style
     * @param PdfTextAlignment[] $aligns the cells alignment
     * @param PdfCell[]          $cells  the cells
     */
    protected function drawRow(PdfDocument $parent, float $height, array $texts, array $widths, array $styles, array $aligns, array $cells): void
    {
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
                    /** @psalm-var float $w */
                    $w = \array_sum($widths);
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
    protected function getCellHeight(?string $text, float $width, PdfStyle $style, PdfCell $cell): float
    {
        $parent = $this->parent;
        if ($cell instanceof PdfImageCell) {
            $height = $parent->pixels2UserUnit($cell->getHeight());
            $margins = 2.0 * $parent->getCellMargin();

            return $height + $margins;
        }

        $style->apply($parent);
        $width = \max(0, $width - $style->getIndent());
        $lines = (float) $parent->getLinesCount($text, $width);
        $height = \fpdf\PdfDocument::LINE_HEIGHT;
        if (!$style->getFont()->isDefaultSize()) {
            $fontSize = $style->getFont()->getSize();
            $margins = 2.0 * $parent->getCellMargin();
            $height = $parent->points2UserUnit($fontSize) + $margins;
        }

        return $lines * $height;
    }

    /**
     * Gets the total columns span.
     *
     * @see PdfCell::getCols()
     */
    protected function getCellsSpan(): int
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
     *
     * @see PdfTable::getCellHeight()
     */
    protected function getRowHeight(array $texts, array $widths, array $styles, array $cells): float
    {
        $height = 0;
        foreach ($texts as $index => $text) {
            $height = \max($height, $this->getCellHeight($text, $widths[$index], $styles[$index], $cells[$index]));
        }

        return $height;
    }

    /**
     * @param PdfCell[] $cells
     * @param bool[]    $fixeds
     * @param float[]   $widths
     */
    private function adjustCellWidths(array $cells, array $fixeds, array &$widths): void
    {
        $count = \count($cells);
        $parent = $this->parent;

        // only 1 cell?
        if (1 === $count) {
            $widths[0] = $parent->getPrintableWidth();

            return;
        }

        // get fixed and resizable widths
        [$fixedWidth, $resizableWidth] = $this->computeCellWidths($fixeds, $widths);

        // update resizable widths
        $remainingWidth = $parent->getPrintableWidth() - $fixedWidth;
        if (!$this->isFloatZero($resizableWidth) && !$this->isFloatZero($remainingWidth) && $resizableWidth !== $remainingWidth) {
            $factor = $remainingWidth / $resizableWidth;
            for ($i = 0; $i < $count; ++$i) {
                if (!$fixeds[$i]) {
                    $widths[$i] *= $factor;
                }
            }
        }
    }

    /**
     * Check if output row is already started.
     *
     * @throws \LogicException
     */
    private function checkRowStarted(): void
    {
        if ($this->isRowStarted()) {
            throw new \LogicException('Row already started.');
        }
    }

    /**
     * @param PdfCell[]   $cells
     * @param PdfColumn[] $columns
     *
     * @return array{
     *     0: string[],
     *     1: PdfStyle[],
     *     2: PdfTextAlignment[],
     *     3: float[],
     *     4: bool[]
     * }
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
            $styles[] = $cell->getStyle() ?? $this->rowStyle ?? PdfStyle::getCellStyle();
            $aligns[] = $cell->getAlignment() ?? $columns[$index]->getAlignment() ?? PdfTextAlignment::LEFT;

            $width = 0.0;
            $fixed = $columns[$index]->isFixed();
            for ($i = 0, $count = $cell->getCols(); $i < $count; ++$i) {
                // check if one of the columns is not fixed
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
     * Compute fixed and resizable widths.
     *
     * @param bool[]  $fixeds
     * @param float[] $widths
     *
     * @return array{0: float, 1: float}
     */
    private function computeCellWidths(array $fixeds, array $widths): array
    {
        $fixedWidth = 0.0;
        $resizableWidth = 0.0;
        foreach ($fixeds as $index => $fixed) {
            if ($fixed) {
                $fixedWidth += $widths[$index];
            } else {
                $resizableWidth += $widths[$index];
            }
        }

        return [$fixedWidth, $resizableWidth];
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
     * @param PdfTextAlignment $alignment the cell alignment
     * @param PdfStyle         $style     the cell style
     * @param PdfCell          $cell      the cell
     */
    private function drawCell(PdfDocument $parent, int $index, float $width, float $height, string $text, PdfTextAlignment $alignment, PdfStyle $style, PdfCell $cell): void
    {
        [$x, $y] = $parent->getXY();
        $bounds = new PdfRectangle($x, $y, $width, $height);

        // background
        $style->apply($parent);
        $this->drawCellBackground($parent, $index, $bounds, $style);

        // border
        $style->apply($parent);
        $parent->setXY($x, $y);
        $border = $style->getBorder() ?? $this->border;
        $this->drawCellBorder($parent, $index, $bounds, $border);

        // image or text
        $style->apply($parent);
        $parent->setXY($x, $y);
        $margin = $parent->getCellMargin();
        $textBounds = clone $bounds;
        $line_height = \fpdf\PdfDocument::LINE_HEIGHT;
        if ($cell instanceof PdfImageCell) {
            $textBounds->inflate(-$margin);
            $cell->drawImage($parent, clone $textBounds, $alignment);
            $textBounds->inflate($margin);
        } else {
            if (!$style->getFont()->isDefaultSize()) {
                $line_height = $parent->getFontSize() + 2.0 * $margin;
            }
            $indent = $style->getIndent();
            if ($indent > 0) {
                $parent->setX($x + $indent);
                $textBounds->indent($indent);
            }
            $this->drawCellText($parent, $index, $textBounds, $text, $alignment, $line_height);
        }

        if ($cell->isLink()) {
            $textBounds->inflate(-$margin);
            $linkWidth = $parent->getStringWidth($text);
            $linesCount = \max(1, $parent->getLinesCount($text, $textBounds->width()));
            $linkHeight = (float) $linesCount * $line_height - 2.0 * $margin;
            $textBounds->setSize($linkWidth, $linkHeight);
            $this->drawCellLink($parent, $textBounds, $cell->getLink());
        }

        $parent->setXY($x + $width, $y);
    }

    /**
     * Draws the cell background.
     *
     * @param PdfDocument  $parent the parent document
     * @param int          $index  the column index
     * @param PdfRectangle $bounds the cell bounds
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

        if ($border->isNone()) {
            return;
        }

        if ($border->isAll()) {
            $parent->rectangle($bounds);

            return;
        }

        // draw each applicable border side
        $x = $bounds->x();
        $y = $bounds->y();
        $right = $bounds->right();
        $bottom = $bounds->bottom();
        if ($border->isLeft()) {
            $parent->line($x, $y, $x, $bottom);
        }
        if ($border->isRight()) {
            $parent->line($right, $y, $right, $bottom);
        }
        if ($border->isTop()) {
            $parent->line($x, $y, $right, $y);
        }
        if ($border->isBottom()) {
            $parent->line($x, $bottom, $right, $bottom);
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
        if (StringUtils::isString($text)) {
            $parent->multiCell(width: $bounds->width(), height: $height, text: $text, align: $alignment);
        }
    }
}
