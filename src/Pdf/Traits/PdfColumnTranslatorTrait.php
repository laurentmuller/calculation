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

use App\Pdf\PdfColumn;
use App\Traits\TranslatorTrait;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Trait to allow adding translatable columns.
 */
trait PdfColumnTranslatorTrait
{
    use TranslatorTrait;

    /**
     * Create a column with center alignment and a translated text.
     *
     * @param string|\Stringable|TranslatableInterface $id    the identifier to translate or an empty string if none
     * @param float                                    $width the column width
     * @param bool                                     $fixed true if the column width is fixed. This property is used
     *                                                        only if the parent's table uses all the document width.
     */
    protected function centerColumn(
        string|\Stringable|TranslatableInterface $id,
        float $width,
        bool $fixed = false
    ): PdfColumn {
        return PdfColumn::center('' !== $id ? $this->trans($id) : $id, $width, $fixed);
    }

    /**
     * Create a column with left alignment and a translated text.
     *
     * @param string|\Stringable|TranslatableInterface $id    the identifier to translate or an empty string if none
     * @param float                                    $width the column width
     * @param bool                                     $fixed true if the column width is fixed. This property is used
     *                                                        only if the parent's table uses all the document width.
     */
    protected function leftColumn(
        string|\Stringable|TranslatableInterface $id,
        float $width,
        bool $fixed = false
    ): PdfColumn {
        return PdfColumn::left('' !== $id ? $this->trans($id) : $id, $width, $fixed);
    }

    /**
     * Create a column with right alignment and a translated text.
     *
     * @param string|\Stringable|TranslatableInterface $id    the identifier to translate or an empty string if none
     * @param float                                    $width the column width
     * @param bool                                     $fixed true if the column width is fixed. This property is used
     *                                                        only if the parent's table uses all the document width.
     */
    protected function rightColumn(
        string|\Stringable|TranslatableInterface $id,
        float $width,
        bool $fixed = false
    ): PdfColumn {
        return PdfColumn::right('' !== $id ? $this->trans($id) : $id, $width, $fixed);
    }
}
