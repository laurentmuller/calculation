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

namespace App\Pdf\Events;

use App\Pdf\Interfaces\PdfGroupListenerInterface;
use App\Pdf\PdfDocument;
use App\Pdf\PdfGroup;
use App\Pdf\PdfGroupTable;

/**
 * The event raised when a group must be rendered.
 *
 * @see PdfGroupListenerInterface
 */
readonly class PdfGroupEvent
{
    /**
     * @param PdfGroupTable $table the parent's table
     * @param PdfGroup      $group the group to output
     */
    public function __construct(
        public PdfGroupTable $table,
        public PdfGroup $group
    ) {
    }

    /**
     * Gets the parent's document.
     *
     * @psalm-api
     */
    public function getDocument(): PdfDocument
    {
        return $this->table->getParent();
    }
}
