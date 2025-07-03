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
use fpdf\Enums\PdfFontName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EnumExtrasTraitTest extends TestCase
{
    /**
     * @phpstan-return \Generator<int, array{0: HtmlTag, 1: string, 2: bool, 3?: true}>
     */
    public static function getExtraBool(): \Generator
    {
        yield [HtmlTag::H1, 'font-bold', true];
        yield [HtmlTag::H1, 'fake-key', true, true];
    }

    /**
     * @phpstan-return \Generator<int, array{0: HtmlTag, 1: string, 2: PdfFontName, 3?: true}>
     */
    public static function getExtraEnum(): \Generator
    {
        yield [HtmlTag::KEYBOARD, 'font-name', PdfFontName::COURIER];
        yield [HtmlTag::H1, 'fake-key', PdfFontName::COURIER, true];
    }

    /**
     * @phpstan-return \Generator<int, array{0: HtmlTag, 1: string, 2: float, 3?: true}>
     */
    public static function getExtraFloat(): \Generator
    {
        yield [HtmlTag::H1, 'font-size', 2.5];
        yield [HtmlTag::H1, 'fake-key', 2.5, true];
    }

    /**
     * @phpstan-return \Generator<int, array{0: TableView, 1: string, 2: int, 3?: true}>
     */
    public static function getExtraInt(): \Generator
    {
        yield [TableView::TABLE, 'page-size', 20];
        yield [TableView::TABLE, 'fake-key', 20, true];
    }

    /**
     * @phpstan-return \Generator<int, array{0: HtmlTag, 1: string, 2: string, 3?: true}>
     */
    public static function getExtraString(): \Generator
    {
        yield [HtmlTag::CODE, 'text-color', '#FF0000'];
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

    #[DataProvider('getExtraEnum')]
    public function testExtraEnum(HtmlTag $tag, string $key, PdfFontName $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(\InvalidArgumentException::class);
        }
        $actual = $tag->getExtraEnum($key, $expected, true);
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
