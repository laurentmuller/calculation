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

use App\Pdf\Enums\PdfTextAlignment;

/**
 * Trait for class implementing the <code>PdfCellListenerInterface</code> interface.
 *
 * By default, do nothing and returns always <code>false</code>.
 * Class can override only the desired methods.
 *
 * @see PdfCellListenerInterface
 */
trait PdfCellListenerTrait
{
    /**
     * {@inheritdoc}
     *
     * @see PdfCellListenerInterface::drawCellBackground()
     */
    public function drawCellBackground(PdfTableBuilder $builder, int $index, PdfRectangle $bounds): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @see PdfCellListenerInterface::drawCellBorder()
     */
    public function drawCellBorder(PdfTableBuilder $builder, int $index, PdfRectangle $bounds, PdfBorder $border): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @see PdfCellListenerInterface::drawCellText()
     */
    public function drawCellText(PdfTableBuilder $builder, int $index, PdfRectangle $bounds, string $text, PdfTextAlignment $align, float $height): bool
    {
        return false;
    }
}
