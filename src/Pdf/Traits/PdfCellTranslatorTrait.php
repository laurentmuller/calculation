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
use App\Traits\TranslatorTrait;
use fpdf\PdfTextAlignment;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Trait to allow adding translatable cells.
 *
 * @psalm-require-extends \App\Pdf\PdfTable
 */
trait PdfCellTranslatorTrait
{
    use TranslatorTrait;

    /**
     * Adds a cell with a translated text to the current row.
     *
     * @param string|\Stringable|TranslatableInterface $id        the message identifier to be translated; may also be
     *                                                            an object that can be cast to string
     * @param int                                      $cols      the number of columns to span
     * @param ?PdfStyle                                $style     the cell style to use or null to use the default
     *                                                            cell style
     * @param ?PdfTextAlignment                        $alignment the cell alignment
     * @param string|int|null                          $link      the optional cell link. A URL or identifier
     *                                                            returned by the <code>addLink()</code> function.
     *
     * @psalm-param positive-int $cols
     */
    public function addCellTrans(
        string|\Stringable|TranslatableInterface $id,
        int $cols = 1,
        ?PdfStyle $style = null,
        ?PdfTextAlignment $alignment = null,
        string|int|null $link = null
    ): static {
        return $this->addCell(new PdfCell($this->trans($id), $cols, $style, $alignment, $link));
    }
}
