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

use App\Interfaces\DefaultEnumInterface;

/**
 * The sort mode enumeration.
 *
 * @implements DefaultEnumInterface<SortMode>
 */
enum SortMode: string implements DefaultEnumInterface
{
    /** Ascending order */
    case ASC = 'asc';
    /** Descending order */
    case DESC = 'desc';

    /** The default enumeration. */
    public const self DEFAULT = self::ASC;

    public function direction(): \SortDirection
    {
        return match ($this) {
            self::ASC => \SortDirection::Ascending,
            self::DESC => \SortDirection::Descending,
        };
    }
}
