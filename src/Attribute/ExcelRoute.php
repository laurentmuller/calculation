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

namespace App\Attribute;

/**
 * Shortcut route attribute to get an Excel document.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class ExcelRoute extends GetRoute
{
    public function __construct()
    {
        parent::__construct('/excel', 'excel');
    }
}
