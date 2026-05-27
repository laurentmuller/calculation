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

namespace App\Pivot\Formatter;

/**
 * The default semester formatter.
 */
readonly class SemesterFormatter extends ArrayFormatter
{
    public function __construct()
    {
        parent::__construct([
            1 => '1st semester',
            2 => '2nd semester',
        ]);
    }
}
