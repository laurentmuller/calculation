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

namespace App\Enums;

/**
 * Table view enumeration.
 */
enum TableView: string
{
    /*
     * The view name to show detailed values.
     */
    case CARD = 'card';
    /*
     * The view name to show values as cards.
     */
    case CUSTOM = 'custom';
    /*
     * The view name to show values within a table (default view).
     */
    case TABLE = 'table';
    /**
     * Gets the default page size.
     */
    public function getPageSize(): int
    {
        return match ($this) {
            self::TABLE => 20,
            self::CARD => 5,
            self::CUSTOM => 15
        };
    }
}
