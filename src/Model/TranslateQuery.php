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
 * Contains translation query parameters.
 */
class TranslateQuery
{
    public function __construct(
        public string $from = '',
        public string $to = '',
        public string $text = '',
        public ?string $service = null,
        public bool $html = false,
    ) {
    }
}
