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

namespace App\Pdf;

use App\Traits\MathTrait;
use App\Util\Utils;

/**
 * Class to build a table.
 *
 * @author Laurent Muller
 *
 * @see \App\Pdf\PdfColumn
 */
class PdfTableBuilder implements PdfConstantsInterface
{
    use MathTrait;
    use PdfAlignmentTrait;
    use PdfBorderTrait;

    /**
     * The cells.
     *
     * @var PdfCell[]
     */
    protected array $cells = [];

    /**
     * The columns.
     *
     * @var PdfColumn[]
     */
    protected array $columns = [];

    /**
     * The header style.
     */
    protected ?PdfStyle $headerStyle = null;

    /**
     * The cell listener.
     */
    protected ?PdfCellListenerInterface $listener = null;

    /**
     * Print headers when a new page is added.
     */
    protected bool $repeatHeader = true;

    /**
     * The current row style.
     */
    protected ?PdfStyle $rowStyle = null;

    /**
     * Constructor.
     *
     * @param PdfDocument $parent    the parent document to print in
     * @param bool        $fullWidth a value indicating if the table take all the printable width
     */
    public function __construct(protected PdfDocument $parent, protected bool $fullWidth = true)
    {
    }

    /**
     * Adds cell to the current row.
     *
     * @param string|null   $text      the text of the cell
     * @param int           $cols      the number of columns to span
     * @param PdfStyle|null $style     the cell style to use or null to use the default cell style
     * @param string|null   $alignment the cell alignment
     */
    public function add(?string $text, int $cols = 1, ?PdfStyle $style = null, ?string $alignment = self::ALIGN_INHERITED): self
    {
        return $this->addCell(new PdfCell($text, $cols, $style, $alignment));
    }

    /**
     * Adds the given cell to the list of cells.
     * Do nothing if the given cell is <code>null</code>.
     *
     * @throws \InvalidArgumentException if no current row is started
     */
    public function addCell(?PdfCell $cell): self
    {
        if (!$this->isRowStarted()) {
            throw new \InvalidArgumentException('No current row is started.');
        }
        if (null !== $cell) {
            $this->cells[] = $cell;
        }

        return $this;
    }

    /**
     * Adds the given cells to the list of cells.
     *
     * @param PdfCell[] $cells the cells to add
     *
     * @throws \InvalidArgumentException if no current row is started
     */
    public function addCells(array $cells): self
    {
        foreach ($cells as $cell) {
            $this->addCell($cell);
        }

        return $this;
    }

    /**
     * Adds the given column to the list of columns.
     */
    public function addColumn(?PdfColumn $column): self
    {
        if (null !== $column) {
            $this->columns[] = $column;
        }

        return $this;
    }

    /**
     * Adds the given columns to the list of columns.
     *
     * @param PdfColumn[] $columns the columns to add
     */
    public function addColumns(array $columns): self
    {
        foreach ($columns as $column) {
            $this->addColumn($column);
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
     * @throws \InvalidArgumentException if a row is not started
     */
    public function completeRow(bool $endRow = true): self
    {
        // started?
        if (!$this->isRowStarted()) {
            throw new \InvalidArgumentException('No row started.');
        }

        // add remaining cells
        $remaining = $this->getColumnsCount() - $this->getCellsSpan();
        for ($i = 0; $i < $remaining; ++$i) {
            $this->add('');
        }
        if ($endRow) {
            return $this->endRow();
        }

        return $this;
    }

    /**
     * Output the current row.
     *
     * After this call, no more cell is defined.
     *
     * @throws \LengthException     if no cell is defined
     * @throws \OutOfRangeException if the number of spanned cells is not equal to the number of columns
     */
    public function endRow(): self
    {
        // check
        if (empty($this->cells)) {
            throw new \LengthException('No cell to add.');
        }
        if ($this->getCellsSpan() !== $this->getColumnsCount()) {
            throw new \OutOfRangeException('Invalid spanned cells.');
        }

        // copy
        $cells = $this->cells;
        $parent = $this->parent;
        $columns = $this->columns;

        /** @psalm-var string[] $texts */
        $texts = [];
        /** @psalm-var PdfStyle[] $styles */
        $styles = [];
        /** @psalm-var string[] $aligns */
        $aligns = [];
        /** @psalm-var float[] $widths */
        $widths = [];
        /** @psalm-var float[] $fixeds */
        $fixeds = [];

        $index = 0;
        foreach ($cells as $cell) {
            $texts[] = $cell->getText() ?: '';
            $styles[] = $cell->getStyle() ?: $this->rowStyle ?: PdfStyle::getCellStyle();
            $aligns[] = $cell->getAlignment() ?: $columns[$index]->getAlignment() ?: self::ALIGN_LEFT;

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

        // update widths
        if ($this->fullWidth) {
            $count = \count($cells);
            if (1 === $count) {
                // only one cell
                $widths[0] = $parent->getPrintableWidth();
            } else {
                // get fixed and resizable widths
                $fixedWidth = 0;
                $resizableWidth = 0;
                for ($i = 0; $i < $count; ++$i) {
                    if ($fixeds[$i]) {
                        $fixedWidth += $widths[$i];
                    } else {
                        $resizableWidth += $widths[$i];
                    }
                }

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
     * Gets the cells.
     *
     * @return PdfCell[] the cells
     */
    public function getCells(): array
    {
        return $this->cells;
    }

    /**
     * Gets the number of cells.
     */
    public function getCellsCount(): int
    {
        return \count($this->cells);
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
     * Gets the cell listener.
     */
    public function getListener(): ?PdfCellListenerInterface
    {
        return $this->listener;
    }

    /**
     * Gets the parent.
     */
    public function getParent(): PdfDocument
    {
        return $this->parent;
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
    public function outputHeaders(): self
    {
        if (empty($this->columns)) {
            throw new \LengthException('No column is defined.');
        }

        $this->startHeaderRow();
        foreach ($this->columns as $column) {
            $this->add($column->getText());
        }

        return $this->endRow();
    }

    /**
     * Output a row.
     *
     * @param PdfCell[]     $cells the cells to output
     * @param PdfStyle|null $style the row style or null for default cell style
     *
     * @throws \InvalidArgumentException if a row is already started
     * @throws \LengthException          if no cell is defined
     * @throws \OutOfRangeException      if the number of spanned cells is not equal to the number of columns
     */
    public function row(array $cells, ?PdfStyle $style = null): self
    {
        return $this->startRow($style)
            ->addCells($cells)
            ->endRow();
    }

    /**
     * Sets a value indicating if the table take all the printable width.
     *
     * @param bool $fullWidth true if the table take all the printable width
     */
    public function setFullWidth(bool $fullWidth): self
    {
        $this->fullWidth = $fullWidth;

        return $this;
    }

    /**
     * Sets the header style.
     *
     * @param PdfStyle|null $headerStyle the custom header style to set or null to use the default header style
     *
     * @see PdfStyle::getHeaderStyle()
     */
    public function setHeaderStyle(?PdfStyle $headerStyle): void
    {
        $this->headerStyle = $headerStyle;
    }

    /**
     * Sets the cell listener.
     */
    public function setListener(?PdfCellListenerInterface $listener): self
    {
        $this->listener = $listener;

        return $this;
    }

    /**
     * Sets if the header row is printed when a new page is added.
     *
     * @param bool $repeatHeader true to print the header on each new pages
     */
    public function setRepeatHeader(bool $repeatHeader): self
    {
        $this->repeatHeader = $repeatHeader;

        return $this;
    }

    /**
     * Output a row with a single cell.
     *
     * @param string|null   $text      the text of the cell
     * @param PdfStyle|null $style     the row style to use or null to use the default cell style
     * @param string|null   $alignment the cell alignment
     *
     * @return self this instance
     *
     * @throws \InvalidArgumentException if a row is already started
     *
     * @see PdfTableBuilder::add()
     */
    public function singleLine(?string $text = null, ?PdfStyle $style = null, ?string $alignment = self::ALIGN_INHERITED): self
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
     * @throws \InvalidArgumentException if a row is already started
     */
    public function startHeaderRow(): self
    {
        return $this->startRow($this->getHeaderStyle());
    }

    /**
     * Starts a new row.
     *
     * @param PdfStyle|null $style the row style to use or null to use the default cell style
     */
    public function startRow(?PdfStyle $style = null): self
    {
        if ($this->isRowStarted()) {
            throw new \InvalidArgumentException('A row is already started.');
        }
        $this->rowStyle = $style ?: PdfStyle::getCellStyle();

        return $this;
    }

    /**
     * Output a single cell. The default behavior is to draw the cell border (if any),
     * fill the cell (if applicable) and draw the text.
     * After this call, the current position is at the top/right of the cell.
     *
     * @param PdfDocument $parent    the parent document
     * @param int         $index     the column index
     * @param float       $width     the cell width
     * @param float       $height    the cell height
     * @param string      $text      the cell text
     * @param string      $alignment the cell alignment
     * @param PdfStyle    $style     the cell style
     * @param PdfCell     $cell      the cell
     */
    protected function drawCell(PdfDocument $parent, int $index, float $width, float $height, string $text, string $alignment, PdfStyle $style, PdfCell $cell): void
    {
        // save the current position
        [$x, $y] = $parent->GetXY();

        // style
        $style->apply($parent);

        // cell bounds
        $bounds = new PdfRectangle($x, $y, $width, $height);

        // cell background
        if ($style->isFillColor()) {
            $this->drawCellBackground($parent, $index, clone $bounds);
            $parent->SetXY($x, $y);
        }

        // cell border
        $border = $style->isBorderInherited() ? $this->border : $style->getBorder();
        if (self::BORDER_NONE !== $border) {
            $this->drawCellBorder($parent, $index, clone $bounds, $border);
            $parent->SetXY($x, $y);
        }

        if ($cell instanceof PdfImageCell) {
            // special case for image cell
            $rect = clone $bounds;
            $rect->inflate(-$parent->getCellMargin());
            $cell->drawImage($parent, $rect, $alignment);
        } elseif (Utils::isString($text)) {
            // cell text
            $lineheight = self::LINE_HEIGHT;
            if (!$style->getFont()->isDefaultSize()) {
                $lineheight = $parent->getFontSize() + 2 * $parent->getCellMargin();
            }
            $textBounds = clone $bounds;
            $indent = $style->getIndent();
            if ($indent > 0) {
                $parent->SetX($x + $indent);
                $textBounds->indent($indent);
            }
            $this->drawCellText($parent, $index, $textBounds, $text, $alignment, $lineheight);
        }

        // move the position to the top-right of the cell
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
        // handle by listener?
        if ($this->listener && $this->listener->onDrawCellBackground($this, $index, $bounds)) {
            return;
        }

        // default
        $parent->rectangle($bounds, self::RECT_FILL);
    }

    /**
     * Draws the cell border.
     *
     * @param PdfDocument  $parent the parent document
     * @param int          $index  the column index
     * @param PdfRectangle $bounds the cell bounds
     * @param int|string   $border the border style
     */
    protected function drawCellBorder(PdfDocument $parent, int $index, PdfRectangle $bounds, int|string $border): void
    {
        // handle by listener?
        if ($this->listener && $this->listener->onDrawCellBorder($this, $index, $bounds, $border)) {
            return;
        }

        // get values
        $x = $bounds->x();
        $y = $bounds->y();

        if (self::BORDER_ALL === $border) {
            // draw all
            $parent->rectangle($bounds, self::RECT_BORDER);
        } elseif (\is_string($border)) {
            // draw each applicable border side
            $right = $bounds->right();
            $bottom = $bounds->bottom();
            if (\str_contains($border, self::BORDER_LEFT)) {
                $parent->Line($x, $y, $x, $bottom);
            }
            if (\str_contains($border, self::BORDER_RIGHT)) {
                $parent->Line($right, $y, $right, $bottom);
            }
            if (\str_contains($border, self::BORDER_TOP)) {
                $parent->Line($x, $y, $right, $y);
            }
            if (\str_contains($border, self::BORDER_BOTTOM)) {
                $parent->Line($x, $bottom, $right, $bottom);
            }
        }
    }

    /**
     * Draws the cell text.
     *
     * @param PdfDocument  $parent    the parent document
     * @param int          $index     the column index
     * @param PdfRectangle $bounds    the cell bounds
     * @param string       $text      the cell text
     * @param string       $alignment the text alignment
     * @param float        $height    the line height
     */
    protected function drawCellText(PdfDocument $parent, int $index, PdfRectangle $bounds, string $text, string $alignment, float $height): void
    {
        // handle by listener?
        if ($this->listener && $this->listener->onDrawCellText($this, $index, $bounds, $text, $alignment, $height)) {
            return;
        }

        // default
        $parent->MultiCell($bounds->width(), $height, $text, self::BORDER_NONE, $alignment);
    }

    /**
     * Output a row.
     *
     * @param PdfDocument $parent the parent document
     * @param float       $height the row height
     * @param string[]    $texts  the cells text
     * @param float[]     $widths the cells width
     * @param PdfStyle[]  $styles the cells style
     * @param string[]    $aligns the cells alignment
     * @param PdfCell[]   $cells  the cells
     */
    protected function drawRow(PdfDocument $parent, float $height, array $texts, array $widths, array $styles, array $aligns, array $cells): void
    {
        // horizontal alignment
        if (!$this->fullWidth) {
            if (self::ALIGN_CENTER === $this->alignment) {
                $w = \array_sum($widths);
                $x = $parent->getLeftMargin() + ($parent->getPrintableWidth() - $w) / 2;
                $parent->SetX($x);
            } elseif (self::ALIGN_RIGHT === $this->alignment) {
                $w = \array_sum($widths);
                $x = $parent->GetPageWidth() - $parent->getRightMargin() - $w;
                $parent->SetX($x);
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
     * @param string|null $text  the cell text
     * @param float       $width the cell width
     * @param PdfStyle    $style the cell style
     * @param PdfCell     $cell  the cell
     *
     * @return float the cell height
     *
     * @see \App\Pdf\PdfTableBuilder::getRowHeight()
     */
    protected function getCellHeight(?string $text, float $width, PdfStyle $style, PdfCell $cell): float
    {
        $parent = $this->parent;

        // image?
        if ($cell instanceof PdfImageCell) {
            $height = $parent->pixels2UserUnit($cell->getHeight());

            return $height + 2 * $parent->getCellMargin();
        }

        $style->apply($parent);
        $width = \max(0, $width - $style->getIndent());
        $lines = $parent->getLinesCount($text, $width);

        $height = self::LINE_HEIGHT;
        if (PdfFont::DEFAULT_SIZE !== $style->getFont()->getSize()) {
            $height = $parent->getFontSize() + 2 * $parent->getCellMargin();
        }

        return $lines * $height;
    }

    /**
     * Gets the total columns span.
     *
     * @return int the number of columns span
     *
     * @see PdfCell::getCols();
     */
    protected function getCellsSpan(): int
    {
        return \array_reduce($this->cells, function (int $carry, PdfCell $cell) {
            return $carry + $cell->getCols();
        }, 0);
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
        $len = \count($texts);
        for ($i = 0; $i < $len; ++$i) {
            $height = \max($height, $this->getCellHeight($texts[$i], $widths[$i], $styles[$i], $cells[$i]));
        }

        return $height;
    }
}
