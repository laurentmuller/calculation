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
 * The sort mode enumeration.
 */
enum SortMode: string
{
    case ASC = 'asc';
    case DESC = 'desc';

    public function direction(): \SortDirection
    {
        return match ($this) {
            self::ASC => \SortDirection::Ascending,
            self::DESC => \SortDirection::Descending,
        };
    }
}
