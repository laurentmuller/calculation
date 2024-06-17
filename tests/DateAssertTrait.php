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
 * @psalm-require-extends TestCase
 */
trait DateAssertTrait
{
    protected static function assertSameDate(\DateTimeInterface $expected, mixed $actual, string $message = ''): void
    {
        if ($actual instanceof \DateTimeInterface) {
            $actual = $actual->getTimestamp();
        }
        self::assertSame($expected->getTimestamp(), $actual, $message);
    }
}
