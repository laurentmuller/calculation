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
 * Trait used as a workaround to use <code>assertEmpty()</code> function within the <code>Countable</code> interface.
 *
 * @require-extends TestCase
 */
trait AssertEmptyTrait
{
    /**
     * @phpstan-assert empty|\Countable $actual
     */
    final public static function assertEmptyCountable(mixed $actual): void
    {
        self::assertEmpty($actual);
    }
}
