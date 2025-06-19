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

namespace App\Tests;

use Elao\Enum\FlagBag;
use PHPUnit\Framework\TestCase;

/**
 * Extend test case for FlagBag class.
 */
abstract class FlagBagTestCase extends TestCase
{
    public static function assertSameFlagBag(mixed $expected, mixed $actual): void
    {
        self::assertInstanceOf(FlagBag::class, $expected);
        self::assertInstanceOf(FlagBag::class, $actual);
        self::assertSame($expected->getValue(), $actual->getValue());
    }
}
