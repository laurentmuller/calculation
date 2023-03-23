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

use App\Pdf\Enums\PdfRectangleStyle;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\Interfaces\PdfDrawCellBackgroundInterface;
use App\Pdf\Interfaces\PdfDrawCellBorderInterface;
use App\Pdf\Interfaces\PdfDrawCellTextInterface;
use App\Traits\MathTrait;
use App\Util\StringUtils;

/**
 * Class to build a table.
 *
 * @see PdfColumn
 */
class PdfTableBuilder
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
     * The header style.
     */
    private ?PdfStyle $headerStyle = null;

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
     * Constructor.
     *
     * @param PdfDocument $parent    the parent document to print in
     * @param bool        $fullWidth a value indicating if the table take all the printable width
     */
    public function __construct(private PdfDocument $parent, private bool $fullWidth = true)
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
        if (null !== $cell) {
            $this->cells[] = $cell;
        }

        return $this;
    }

    /**
     * Adds the given column to the list of columns.
     *
     * Do nothing if the column is null.
     *
     * @see PdfTableBuilder::addColumns()
     */
    public function addColumn(?PdfColumn $column): static
    {
        if (null !== $column) {
            $this->columns[] = $column;
        }

        return $this;
    }

    /**
     * Adds the given columns to the list of columns.
     *
     * The null columns are not added.
     *
     * @see PdfTableBuilder::addColumn()
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
     * @throws \LogicException if the row is already started
     */
    public function addHeaderRow(PdfCell|string|null ...$values): static
    {
        return $this->startHeaderRow()
            ->addValues(...$values)
            ->completeRow();
    }

    /**
     * Create and add a row with the given values.
     *
     * @throws \LogicException if the row is already started
     *
     * @see PdfTableBuilder::addStyledRow()
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
     * @throws \LengthException     if no cell is defined
     * @throws \OutOfRangeException if the number of spanned cells is not equal to the number of columns
     *
     * @see PdfTableBuilder::addRow()
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
     */
    public function checkNewPage(float $height): bool
    {
        $parent = $this->parent;
        if (!$parent->isPrintable($height)) {
            $parent->AddPage();
            if ($this->repeatHeader) {
                $this->outputHeaders();
            }

            return true;
        }

        return false;
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
        for ($i = 0, $remaining = $this->getColumnsCount() - $this->getCellsSpan(); $i < $remaining; ++$i) {
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
        if ($this->getCellsSpan() !== $this->getColumnsCount()) {
            throw new \OutOfRangeException(\sprintf('Invalid spanned cells: expected %d, %d given.', $this->getColumnsCount(), $this->getCellsSpan()));
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
     * Returns a value indicating if a row is currently started.
     */
    public function isRowStarted(): bool
    {
        return null !== $this->rowStyle;
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

        return $this->addHeaderRow(...\array_map(fn (PdfColumn $c): ?string => $c->getText(), $this->columns));
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
    public function setBorder(PdfBorder|string|int $border): static
    {
        $this->border = \is_string($border) || \is_int($border) ? new PdfBorder($border) : $border;

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
     * Sets if the header row is printed when a new page is added.
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
     * @throws \LogicException if a row is already started
     *
     * @see PdfTableBuilder::add()
     */
    public function singleLine(?string $text = null, ?PdfStyle $style = null, ?PdfTextAlignment $alignment = null): static
    {
        return $this->startRow()
            ->add($text, $this->getColumnsCount(), $style, $alignment)
            ->endRow();
    }

    /**
     * Starts a new row with the custom header style, if set; with the default header style otherwise.
     *
     * @see PdfTableBuilder::getHeaderStyle()
     * @see PdfStyle::getHeaderStyle()
     *
     * @throws \LogicException if a row is already started
     */
    public function startHeaderRow(): static
    {
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
        if ($this->isRowStarted()) {
            throw new \LogicException('Row already started.');
        }
        $this->rowStyle = $style ?? PdfStyle::getCellStyle();

        return $this;
    }

    /**
     * Output a single cell. The default behavior is to draw the cell border (if any),
     * fill the cell (if applicable) and draw the text.
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
    protected function drawCell(PdfDocument $parent, int $index, float $width, float $height, string $text, PdfTextAlignment $alignment, PdfStyle $style, PdfCell $cell): void
    {
        [$x, $y] = $parent->GetXY();
        $style->apply($parent);
        $bounds = new PdfRectangle($x, $y, $width, $height);

        if ($style->isFillColor()) {
            $this->drawCellBackground($parent, $index, clone $bounds);
            $parent->SetXY($x, $y);
        }

        $border = $style->getBorder()->isInherited() ? $this->border : $style->getBorder();
        if ($border->isDrawable()) {
            $this->drawCellBorder($parent, $index, clone $bounds, $border);
            $parent->SetXY($x, $y);
        }

        $margins = $parent->getCellMargin();
        if ($cell instanceof PdfImageCell) {
            $imageBounds = clone $bounds;
            $imageBounds->inflate(-$margins);
            $cell->drawImage($parent, $imageBounds, $alignment);
        } elseif (StringUtils::isString($text)) {
            $line_height = PdfDocument::LINE_HEIGHT;
            if (!$style->getFont()->isDefaultSize()) {
                $line_height = $parent->getFontSize() + 2.0 * $margins;
            }
            $textBounds = clone $bounds;
            $indent = $style->getIndent();
            if ($indent > 0) {
                $parent->SetX($x + $indent);
                $textBounds->indent($indent);
            }
            $this->drawCellText($parent, $index, $textBounds, $text, $alignment, $line_height);

            if ($link = $cell->getLink()) {
                $linkBounds = clone $textBounds;
                $linkBounds->inflate(-$margins);
                $linkWidth = $parent->GetStringWidth($text);
                $linkHeight = (float) $parent->getLinesCount($text, $textBounds->width()) * $line_height - 2.0 * $margins;
                $linkBounds->setSize($linkWidth, $linkHeight);
                $this->drawCellLink($parent, $linkBounds, $link);
            }
        }

        $parent->SetXY($x + $width, $y);
    }

    /**
     * Draws the cell background.
     *
     * @param PdfDocument  $parent the parent document
     * @param int          $index  the column index
     * @param PdfRectangle $bounds the cell bounds
     */
    protected function drawCellBackground(PdfDocument $parent, int $index, PdfRectangle $bounds): void
    {
        if (null !== $this->backgroundListener && $this->backgroundListener->drawCellBackground($this, $index, $bounds)) {
            return;
        }
        $parent->rectangle($bounds, PdfRectangleStyle::FILL);
    }

    /**
     * Draws the cell border.
     *
     * @param PdfDocument  $parent the parent document
     * @param int          $index  the column index
     * @param PdfRectangle $bounds the cell bounds
     * @param PdfBorder    $border the border style
     */
    protected function drawCellBorder(PdfDocument $parent, int $index, PdfRectangle $bounds, PdfBorder $border): void
    {
        if (null !== $this->borderListener && $this->borderListener->drawCellBorder($this, $index, $bounds, $border)) {
            return;
        }
        $x = $bounds->x();
        $y = $bounds->y();
        if ($border->isRectangleStyle()) {
            $parent->rectangle($bounds, $border);
        } else {
            // draw each applicable border side
            $right = $bounds->right();
            $bottom = $bounds->bottom();
            if ($border->isLeft()) {
                $parent->Line($x, $y, $x, $bottom);
            }
            if ($border->isRight()) {
                $parent->Line($right, $y, $right, $bottom);
            }
            if ($border->isTop()) {
                $parent->Line($x, $y, $right, $y);
            }
            if ($border->isBottom()) {
                $parent->Line($x, $bottom, $right, $bottom);
            }
        }
    }

    /**
     * Draws the cell link.
     *
     * @param PdfDocument  $parent the parent document
     * @param PdfRectangle $bounds the link bounds
     * @param string|int   $link   the link URL. A URL or identifier returned by AddLink().
     */
    protected function drawCellLink(PdfDocument $parent, PdfRectangle $bounds, string|int $link): void
    {
        $parent->Link($bounds->x(), $bounds->y(), $bounds->width(), $bounds->height(), $link);
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
    protected function drawCellText(PdfDocument $parent, int $index, PdfRectangle $bounds, string $text, PdfTextAlignment $alignment, float $height): void
    {
        if (null !== $this->textListener && $this->textListener->drawCellText($this, $index, $bounds, $text, $alignment, $height)) {
            return;
        }
        $parent->MultiCell(w: $bounds->width(), h: $height, txt: $text, align: $alignment);
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
                    /** @psalm-var float $w */
                    $w = \array_sum($widths);
                    $x = $parent->getLeftMargin() + ($parent->getPrintableWidth() - $w) / 2.0;
                    $parent->SetX($x);
                    break;
                case PdfTextAlignment::RIGHT:
                    /** @psalm-var float $w */
                    $w = \array_sum($widths);
                    $x = $parent->GetPageWidth() - $parent->getRightMargin() - $w;
                    $parent->SetX($x);
                    break;
                default:
                    break;
            }
        }

        // output cells
        $count = \count($texts);
        for ($i = 0; $i < $count; ++$i) {
            $this->drawCell($parent, $i, $widths[$i], $height, $texts[$i], $aligns[$i], $styles[$i], $cells[$i]);
        }

        // next line
        $parent->Ln($height);
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
        $height = PdfDocument::LINE_HEIGHT;
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
     * @return int the number of columns span
     *
     * @see PdfCell::getCols()
     */
    protected function getCellsSpan(): int
    {
        return \array_reduce($this->cells, fn (int $carry, PdfCell $cell) => $carry + $cell->getCols(), 0);
    }

    /**
     * Gets the row height.
     *
     * @param string[]   $texts  the cell texts
     * @param float[]    $widths the cell widths
     * @param PdfStyle[] $styles the cell styles
     * @param PdfCell[]  $cells  the cells
     *
     * @return float the line height
     *
     * @see PdfTableBuilder::getCellHeight()
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
}
