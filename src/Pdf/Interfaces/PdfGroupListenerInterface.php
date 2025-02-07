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

use App\Pdf\Events\PdfGroupEvent;

/**
 * Class implementing this interface deals with group render.
 */
interface PdfGroupListenerInterface
{
    /**
     * Called when a group must be drawn.
     *
     * @param PdfGroupEvent $event the event
     *
     * @return bool true if the listener handles the output; false to use the default output
     */
    public function drawGroup(PdfGroupEvent $event): bool;
}
