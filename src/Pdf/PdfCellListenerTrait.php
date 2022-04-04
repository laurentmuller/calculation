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

/**
 * Trait for class implementing the <code>PdfCellListenerInterface</code> interface.
 *
 * By default, do nothing and returns always <code>false</code>.
 * Class can override only the desired methods.
 *
 * @author Laurent Muller
 *
 * @see PdfCellListenerInterface
 */
trait PdfCellListenerTrait
{
    /**
     * {@inheritdoc}
     *
     * @see PdfCellListenerInterface::onDrawCellBackground()
     */
    public function onDrawCellBackground(PdfTableBuilder $builder, int $index, PdfRectangle $bounds): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @see PdfCellListenerInterface::onDrawCellBorder()
     */
    public function onDrawCellBorder(PdfTableBuilder $builder, int $index, PdfRectangle $bounds, string|int $border): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @see PdfCellListenerInterface::onDrawCellText()
     */
    public function onDrawCellText(PdfTableBuilder $builder, int $index, PdfRectangle $bounds, string $text, string $align, float $height): bool
    {
        return false;
    }
}
