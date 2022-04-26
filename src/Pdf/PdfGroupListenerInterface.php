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

/**
 * Class implementing this interface deals with group render.
 */
interface PdfGroupListenerInterface
{
    /**
     * Called when a group must be rendered.
     *
     * @param PdfGroupTableBuilder $parent the parent table
     * @param PdfGroup             $group  the group to output
     *
     * @return bool true if the listener handle the output; false to use the default output
     */
    public function onOutputGroup(PdfGroupTableBuilder $parent, PdfGroup $group): bool;
}
