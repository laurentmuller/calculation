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

use App\Pdf\Events\PdfDrawHeadersEvent;

/**
 * Class implementing this interface handles the draw table headers event.
 */
interface PdfDrawHeadersInterface
{
    /**
     * Called when headers must be drawn.
     *
     * @param PdfDrawHeadersEvent $event the event
     *
     * @return bool true if the listener handles the draw function; false to call the default behavior
     */
    public function drawHeaders(PdfDrawHeadersEvent $event): bool;
}
