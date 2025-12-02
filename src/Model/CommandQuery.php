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

class CommandQuery
{
    public function __construct(
        /**
         * The command name.
         */
        public ?string $name = null,
        /**
         * Expanded arguments.
         */
        public bool $arguments = false,
        /**
         * Expanded options.
         */
        public bool $options = false,
        /**
         * Expanded help.
         */
        public bool $help = false,
    ) {
    }
}
