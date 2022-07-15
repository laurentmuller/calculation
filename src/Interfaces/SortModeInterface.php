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

namespace App\Interfaces;

/**
 * Define the sort orders.
 */
interface SortModeInterface
{
    /**
     * The ascending sort order.
     */
    final public const SORT_ASC = 'asc';

    /**
     * The descending sort order.
     */
    final public const SORT_DESC = 'desc';
}
