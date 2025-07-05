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

use PHPUnit\Framework\TestCase;

/**
 * Trait to compare dates.
 *
 * @phpstan-require-extends TestCase
 */
trait DateAssertTrait
{
    public static function assertDateEquals(\DateTimeInterface $expected, mixed $actual): void
    {
        if ($actual instanceof \DateTimeInterface) {
            $actual = $actual->format('Y-m-d');
        }
        self::assertSame($expected->format('Y-m-d'), $actual);
    }

    public static function assertDateTimeEquals(\DateTimeInterface $expected, mixed $actual): void
    {
        if ($actual instanceof \DateTimeInterface) {
            $actual = $actual->format('Y-m-d H:i:s');
        }
        self::assertSame($expected->format('Y-m-d H:i:s'), $actual);
    }

    public static function assertTimeEquals(\DateTimeInterface $expected, mixed $actual): void
    {
        if ($actual instanceof \DateTimeInterface) {
            $actual = $actual->format('H:i:s');
        }
        self::assertSame($expected->format('H:i:s'), $actual);
    }

    public static function assertTimestampEquals(\DateTimeInterface $expected, mixed $actual, string $message = ''): void
    {
        if ($actual instanceof \DateTimeInterface) {
            $actual = $actual->getTimestamp();
        }
        self::assertSame($expected->getTimestamp(), $actual, $message);
    }
}
