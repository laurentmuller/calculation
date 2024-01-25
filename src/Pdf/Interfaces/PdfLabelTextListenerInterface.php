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

use App\Pdf\Events\PdfLabelTextEvent;

/**
 * Class implementing this interface handle the draw label texts event.
 */
interface PdfLabelTextListenerInterface
{
    /**
     * Called when a line must be drawn within the label.
     *
     * @param PdfLabelTextEvent $event the event
     *
     * @return bool true if listener handle the draw function; false to call the default behavior
     */
    public function drawLabelText(PdfLabelTextEvent $event): bool;
}
