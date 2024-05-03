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

namespace App\Tests\Traits;

use App\Enums\TableView;
use App\Pdf\Html\HtmlTag;
use App\Traits\EnumExtrasTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnumExtrasTrait::class)]
class EnumExtrasTraitTest extends TestCase
{
    public static function getExtraBool(): \Iterator
    {
        yield [HtmlTag::H1, 'font-bold', true];
        yield [HtmlTag::H1, 'fake-key', true, true];
    }

    public static function getExtraFloat(): \Iterator
    {
        yield [HtmlTag::H1, 'font-size', 2.5];
        yield [HtmlTag::H1, 'fake-key', 2.5, true];
    }

    public static function getExtraInt(): \Iterator
    {
        yield [TableView::TABLE, 'page-size', 20];
        yield [TableView::TABLE, 'fake-key', 20, true];
    }

    public static function getExtraString(): \Iterator
    {
        yield [HtmlTag::KEYBOARD, 'font-name', 'courier'];
        yield [HtmlTag::KEYBOARD, 'fake-key', 'courier', true];
    }

    #[DataProvider('getExtraBool')]
    public function testExtraBool(HtmlTag $tag, string $key, bool $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(\InvalidArgumentException::class);
        }
        $actual = $tag->getExtraBool($key, throwOnMissingExtra: true);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getExtraFloat')]
    public function testExtraFloat(HtmlTag $tag, string $key, float $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(\InvalidArgumentException::class);
        }
        $actual = $tag->getExtraFloat($key, throwOnMissingExtra: true);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getExtraInt')]
    public function testExtraInt(TableView $view, string $key, int $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(\InvalidArgumentException::class);
        }
        $actual = $view->getExtraInt($key, throwOnMissingExtra: true);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getExtraString')]
    public function testExtraString(HtmlTag $tag, string $key, string $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(\InvalidArgumentException::class);
        }
        $actual = $tag->getExtraString($key, throwOnMissingExtra: true);
        self::assertSame($expected, $actual);
    }
}
