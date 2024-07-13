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

namespace App\Tests\Pdf\Html;

use App\Pdf\Html\HtmlMargins;
use PHPUnit\Framework\TestCase;

class HtmlMarginsTest extends TestCase
{
    public function testDefault(): void
    {
        $actual = HtmlMargins::default();
        self::assertSameMargins($actual);
    }

    public function testReset(): void
    {
        $expected = 10.0;
        $actual = HtmlMargins::default();
        $actual->setBottom($expected);
        $actual->setLeft($expected);
        $actual->setRight($expected);
        $actual->setTop($expected);
        self::assertSameMargins($actual, $expected);

        $actual->reset();
        self::assertSameMargins($actual);
    }

    public function testSetMargins(): void
    {
        $expected = 10.0;
        $actual = HtmlMargins::default();
        $actual->setMargins($expected);
        self::assertSameMargins($actual, $expected);
    }

    protected static function assertSameMargins(HtmlMargins $actual, float $expected = 0.0): void
    {
        self::assertSame($expected, $actual->getBottom());
        self::assertSame($expected, $actual->getLeft());
        self::assertSame($expected, $actual->getRight());
        self::assertSame($expected, $actual->getTop());
    }
}
