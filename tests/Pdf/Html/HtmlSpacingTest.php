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

use App\Pdf\Html\HtmlSpacing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HtmlSpacingTest extends TestCase
{
    /**
     * @psalm-return \Generator<array-key, array{string}>
     */
    public static function getInvalidClasses(): \Generator
    {
        yield ['fake'];
        yield ['mm-0'];
        yield ['m-6'];
        yield ['mt-6'];
        yield ['mb-6'];
        yield ['ms-6'];
        yield ['me-6'];
        yield ['mx-6'];
        yield ['my-6'];
    }

    /**
     * @psalm-return \Generator<array-key, array{string, bool}>
     */
    public static function getIsAll(): \Generator
    {
        yield ['m-0', true];
        yield ['mt-0', false];
        yield ['mb-0', false];
        yield ['ms-0', false];
        yield ['me-0', false];
        yield ['mx-0', false];
        yield ['my-0', false];
    }

    /**
     * @psalm-return \Generator<array-key, array{string}>
     */
    public static function getIsNone(): \Generator
    {
        yield ['m-0'];
        yield ['mt-0'];
        yield ['mb-0'];
        yield ['ms-0'];
        yield ['me-0'];
        yield ['mx-0'];
        yield ['my-0'];
    }

    /**
     * @psalm-return \Generator<array-key, array{string, int, bool, bool, bool, bool}>
     */
    public static function getValidClasses(): \Generator
    {
        yield ['m-0', 0, true, true, true, true];
        yield ['M-0', 0, true, true, true, true];
        yield ['m-1', 1, true, true, true, true];
        yield ['m-2', 2, true, true, true, true];
        yield ['m-3', 3, true, true, true, true];
        yield ['m-4', 4, true, true, true, true];
        yield ['m-5', 5, true, true, true, true];

        yield ['mt-1', 1, true, false, false, false];
        yield ['mb-1', 1, false, true, false, false];

        yield ['ms-0', 0, false, false, true, false];
        yield ['me-0', 0, false, false, false, true];

        yield ['mx-0', 0, false, false, true, true];
        yield ['my-0', 0, true, true, false, false];
    }

    public function testDefault(): void
    {
        $spacing = new HtmlSpacing();
        self::assertFalse($spacing->top);
        self::assertFalse($spacing->bottom);
        self::assertFalse($spacing->left);
        self::assertFalse($spacing->right);
        self::assertFalse($spacing->isAll());
        self::assertTrue($spacing->isNone());
        self::assertSame(0, $spacing->size);
    }

    #[DataProvider('getInvalidClasses')]
    public function testInvalidClass(string $class): void
    {
        $spacing = HtmlSpacing::instance($class);
        self::assertNull($spacing);
    }

    #[DataProvider('getIsAll')]
    public function testIsAll(string $class, bool $expected): void
    {
        $spacing = HtmlSpacing::instance($class);
        self::assertNotNull($spacing);
        self::assertSame($expected, $spacing->isAll());
    }

    #[DataProvider('getIsNone')]
    public function testIsNone(string $class): void
    {
        $spacing = HtmlSpacing::instance($class);
        self::assertNotNull($spacing);
        self::assertFalse($spacing->isNone());
    }

    #[DataProvider('getValidClasses')]
    public function testValidClass(
        string $class,
        int $size,
        bool $top,
        bool $bottom,
        bool $left,
        bool $right,
    ): void {
        $spacing = HtmlSpacing::instance($class);
        self::assertNotNull($spacing);
        self::assertSame($size, $spacing->size);
        self::assertSame($top, $spacing->top);
        self::assertSame($bottom, $spacing->bottom);
        self::assertSame($left, $spacing->left);
        self::assertSame($right, $spacing->right);
    }
}
