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

namespace App\Pdf\Traits;

use App\Pdf\PdfCell;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Utils\FormatUtils;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfException;

/**
 * Trait to add cells with formatted values.
 *
 * @psalm-require-extends PdfTable
 */
trait PdfCellFormatTrait
{
    /**
     * Adds a right-aligned cell, with formatted value as amount, to the current row.
     *
     * @param float|int|string|null $number the number to format
     * @param positive-int          $cols   the number of columns to span
     * @param ?PdfStyle             $style  the cell style to use or null to use the default cell style
     * @param string|int|null       $link   the optional cell link.
     *                                      A URL or identifier returned by the <code>addLink()</code> function.
     *
     * @throws PdfException if no current row is started
     */
    public function addCellAmount(
        float|int|string|null $number,
        int $cols = 1,
        ?PdfStyle $style = null,
        ?PdfTextAlignment $alignment = PdfTextAlignment::RIGHT,
        string|int|null $link = null
    ): static {
        return $this->addCell(
            new PdfCell(FormatUtils::formatAmount($number), $cols, $style, $alignment, $link)
        );
    }

    /**
     * Adds a right-aligned cell, with the formatted value as integer, to the current row.
     *
     * @param \Countable|array|int|float|string|null $number the number to format
     * @param positive-int                           $cols   the number of columns to span
     * @param ?PdfStyle                              $style  the cell style to use or null to use the default cell style
     * @param string|int|null                        $link   the optional cell link. A URL or identifier returned by
     *                                                       the <code>addLink()</code> function.
     *
     * @throws PdfException if no current row is started
     */
    public function addCellInt(
        \Countable|array|int|float|string|null $number,
        int $cols = 1,
        ?PdfStyle $style = null,
        ?PdfTextAlignment $alignment = PdfTextAlignment::RIGHT,
        string|int|null $link = null
    ): static {
        return $this->addCell(
            new PdfCell(FormatUtils::formatInt($number), $cols, $style, $alignment, $link)
        );
    }

    /**
     * Adds a right-aligned cell, with formatted value as percent, to the current row.
     *
     * @param float|int|string|null $number the number to format
     * @param positive-int          $cols   the number of columns to span
     * @param ?PdfStyle             $style  the cell style to use or null to use the default cell style
     * @param string|int|null       $link   the optional cell link.
     *                                      A URL or identifier returned by the <code>addLink()</code> function.
     *
     * @throws PdfException if no current row is started
     */
    public function addCellPercent(
        float|int|string|null $number,
        int $cols = 1,
        ?PdfStyle $style = null,
        ?PdfTextAlignment $alignment = PdfTextAlignment::RIGHT,
        string|int|null $link = null
    ): static {
        return $this->addCell(
            new PdfCell(FormatUtils::formatPercent($number), $cols, $style, $alignment, $link)
        );
    }
}
