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
use fpdf\PdfBorder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HtmlSpacingTest extends TestCase
{
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

    public static function getValidClasses(): \Generator
    {
        yield ['m-0', 0, PdfBorder::all()];
        yield ['M-0', 0, PdfBorder::all()];
        yield ['m-1', 1, PdfBorder::all()];
        yield ['m-2', 2, PdfBorder::all()];
        yield ['m-3', 3, PdfBorder::all()];
        yield ['m-4', 4, PdfBorder::all()];
        yield ['m-5', 5, PdfBorder::all()];

        yield ['mt-1', 1, PdfBorder::top()];
        yield ['mb-1', 1, PdfBorder::bottom()];
        yield ['MB-1', 1, PdfBorder::bottom()];

        yield ['ms-0', 0, PdfBorder::left()];
        yield ['me-0', 0, PdfBorder::right()];

        yield ['mx-0', 0, PdfBorder::leftRight()];
        yield ['my-0', 0, PdfBorder::topBottom()];
        yield ['MY-0', 0, PdfBorder::topBottom()];
    }

    public function testDefault(): void
    {
        $actual = new HtmlSpacing();
        self::assertSame(0, $actual->size);
        self::assertFalse($actual->left);
        self::assertFalse($actual->top);
        self::assertFalse($actual->right);
        self::assertFalse($actual->bottom);
        self::assertFalse($actual->isAll());
        self::assertTrue($actual->isNone());
    }

    #[DataProvider('getInvalidClasses')]
    public function testInvalidClass(string $class): void
    {
        $actual = HtmlSpacing::parse($class);
        self::assertNull($actual);
    }

    #[DataProvider('getIsAll')]
    public function testIsAll(string $class, bool $expected): void
    {
        $actual = HtmlSpacing::parse($class);
        self::assertNotNull($actual);
        self::assertSame($expected, $actual->isAll());
    }

    #[DataProvider('getIsNone')]
    public function testIsNone(string $class): void
    {
        $actual = HtmlSpacing::parse($class);
        self::assertNotNull($actual);
        self::assertFalse($actual->isNone());
    }

    #[DataProvider('getValidClasses')]
    public function testValidClass(
        string $class,
        int $size,
        PdfBorder $expected,
    ): void {
        $actual = HtmlSpacing::parse($class);
        self::assertNotNull($actual);
        self::assertSame($size, $actual->size);
        self::assertSame($expected->left, $actual->left);
        self::assertSame($expected->top, $actual->top);
        self::assertSame($expected->right, $actual->right);
        self::assertSame($expected->bottom, $actual->bottom);
    }
}
