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
 * The default quarter formatter.
 */
readonly class QuarterFormatter extends ArrayFormatter
{
    public function __construct()
    {
        parent::__construct([
            1 => '1st quarter',
            2 => '2nd quarter',
            3 => '3rd quarter',
            4 => '4th quarter',
        ]);
    }
}
