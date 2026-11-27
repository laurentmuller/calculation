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
    protected static function assertDateEquals(\DateTimeInterface|string $expected, mixed $actual): void
    {
        if ($expected instanceof \DateTimeInterface) {
            $expected = $expected->format('Y-m-d');
        }
        if ($actual instanceof \DateTimeInterface) {
            $actual = $actual->format('Y-m-d');
        }
        self::assertSame($expected, $actual);
    }

    protected static function assertDateTimeEquals(\DateTimeInterface|string $expected, mixed $actual): void
    {
        if ($expected instanceof \DateTimeInterface) {
            $expected = $expected->format('Y-m-d H:i:s');
        }
        if ($actual instanceof \DateTimeInterface) {
            $actual = $actual->format('Y-m-d H:i:s');
        }
        self::assertSame($expected, $actual);
    }

    protected static function assertTimeEquals(\DateTimeInterface|string $expected, mixed $actual): void
    {
        if ($expected instanceof \DateTimeInterface) {
            $expected = $expected->format('H:i:s');
        }
        if ($actual instanceof \DateTimeInterface) {
            $actual = $actual->format('H:i:s');
        }
        self::assertSame($expected, $actual);
    }

    protected static function assertTimestampEquals(\DateTimeInterface|int $expected, mixed $actual): void
    {
        if ($expected instanceof \DateTimeInterface) {
            $expected = $expected->getTimestamp();
        }
        if ($actual instanceof \DateTimeInterface) {
            $actual = $actual->getTimestamp();
        }
        self::assertSame($expected, $actual);
    }
}
