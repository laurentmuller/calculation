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

namespace App\Tests\Data;

use App\Interfaces\EnumDefaultInterface;
use App\Traits\EnumDefaultTrait;

/**
 * @implements EnumDefaultInterface<NoDefaultEnum>
 */
enum NoDefaultEnum implements EnumDefaultInterface
{
    use EnumDefaultTrait;

    case DEBUG;
    case PRODUCTION;
    case TEST;
}
