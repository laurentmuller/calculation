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

namespace App\Model;

/**
 * Query parameters for the home page.
 */
class IndexQuery
{
    public function __construct(
        public ?bool $restrict = null,
        public ?bool $custom = null,
        public ?int $count = null
    ) {
    }
}
