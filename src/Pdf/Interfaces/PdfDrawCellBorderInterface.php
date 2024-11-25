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

namespace App\Pdf\Interfaces;

use App\Pdf\Events\PdfCellBorderEvent;

/**
 * Class implementing this interface handles the draw cell border event.
 */
interface PdfDrawCellBorderInterface
{
    /**
     * Called when a cell border must be drawn.
     *
     * @param PdfCellBorderEvent $event the event
     *
     * @return bool true if the listener handles the draw function; false to call the default behavior
     */
    public function drawCellBorder(PdfCellBorderEvent $event): bool;
}
