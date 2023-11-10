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

use App\Pdf\Events\PdfHeadersEvent;

/**
 * Class implementing this interface deals with table headers.
 */
interface PdfOutputHeadersListener
{
    /**
     * Raised before or after headers are rendered.
     *
     * @param PdfHeadersEvent $event the event
     */
    public function outputHeaders(PdfHeadersEvent $event): void;
}
