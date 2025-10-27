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

namespace App\Tests\Pivot\Field;

use App\Pivot\Field\PivotMethod;
use PHPUnit\Framework\TestCase;

final class PivotMethodTest extends TestCase
{
    public function testConvertFloat(): void
    {
        self::assertSame(0.0, PivotMethod::FLOAT->convert(null));
        self::assertSame(0.0, PivotMethod::FLOAT->convert(''));
        self::assertSame(0.0, PivotMethod::FLOAT->convert('Test'));
        self::assertSame(0.0, PivotMethod::FLOAT->convert(0));
        self::assertSame(1.0, PivotMethod::FLOAT->convert(1));
        self::assertSame(1.1, PivotMethod::FLOAT->convert(1.1));
    }

    public function testConvertInteger(): void
    {
        self::assertSame(0, PivotMethod::INTEGER->convert(null));
        self::assertSame(0, PivotMethod::INTEGER->convert(''));
        self::assertSame(0, PivotMethod::INTEGER->convert('Test'));
        self::assertSame(0, PivotMethod::INTEGER->convert(0));
        self::assertSame(1, PivotMethod::INTEGER->convert(1));
        self::assertSame(1, PivotMethod::INTEGER->convert(1.1));
    }

    public function testConvertString(): void
    {
        self::assertSame('', PivotMethod::STRING->convert(null));
        self::assertSame('', PivotMethod::STRING->convert(''));
        self::assertSame('Test', PivotMethod::STRING->convert('Test'));
        self::assertSame('0', PivotMethod::STRING->convert(0));
        self::assertSame('1', PivotMethod::STRING->convert(1));
        self::assertSame('1.1', PivotMethod::STRING->convert(1.1));
    }
}
