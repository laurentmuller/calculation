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

namespace App\Enums;

/**
 * Table view enumeration.
 */
enum TableView: string
{
    /*
     * Show detailed values.
     */
    case CARD = 'card';
    /*
     * Show values as cards.
     */
    case CUSTOM = 'custom';
    /*
     * Show values within a table (default view).
     */
    case TABLE = 'table';
    /**
     * Gets the default page size.
     */
    public function getPageSize(): int
    {
        return match ($this) {
            self::CARD => 5,
            self::CUSTOM => 15,
            self::TABLE => 20
        };
    }
}
