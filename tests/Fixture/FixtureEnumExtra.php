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

namespace App\Tests\Fixture;

use App\Traits\EnumExtrasTrait;
use Elao\Enum\Attribute\EnumCase;

enum FixtureEnumExtra
{
    use EnumExtrasTrait;

    #[EnumCase(
        extras: [
            'bool' => true,
            'bool_on' => 'on',
            'bool_yes' => 'yes',
            'bool_true' => 'true',
            'int' => 1,
            'int_numeric' => '1.0',
            'float' => 1.0,
            'float_numeric' => '1',
            'string' => 'string',
            'enum' => FixtureEnumExtra::DEFAULT,
        ],
    )]
    case DEFAULT;
}
