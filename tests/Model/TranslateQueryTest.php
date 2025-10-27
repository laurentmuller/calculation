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

namespace App\Tests\Model;

use App\Model\TranslateQuery;
use PHPUnit\Framework\TestCase;

final class TranslateQueryTest extends TestCase
{
    public function testWithDefaultValues(): void
    {
        $actual = new TranslateQuery();
        self::assertSame('', $actual->from);
        self::assertSame('', $actual->to);
        self::assertSame('', $actual->text);
        self::assertNull($actual->service);
        self::assertFalse($actual->html);
    }

    public function testWithValues(): void
    {
        $actual = new TranslateQuery('from', 'to', 'text', 'service', true);
        self::assertSame('from', $actual->from);
        self::assertSame('to', $actual->to);
        self::assertSame('text', $actual->text);
        self::assertSame('service', $actual->service);
        self::assertTrue($actual->html);
    }
}
