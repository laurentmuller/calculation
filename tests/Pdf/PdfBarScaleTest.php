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

namespace App\Tests\Pdf;

use App\Pdf\PdfBarScale;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PdfBarScaleTest extends TestCase
{
    public static function getValues(): \Generator
    {
        yield [self::createScale(0.0, 9.0), 0.0, 10.0,  1.0];
        yield [self::createScale(0.0, 9.1), 0.0, 10.0,  1.0];
        yield [self::createScale(0.0, 9.4), 0.0, 10.0,  1.0];
        yield [self::createScale(0.0, 9.5), 0.0, 10.0,  1.0];
        yield [self::createScale(0.0, 9.6), 0.0, 10.0,  1.0];
        yield [self::createScale(0.0, 9.9), 0.0, 10.0,  1.0];

        yield [self::createScale(0.0, 9.5, 5), 0.0, 10.0,  2.0];
        yield [self::createScale(-9.5, 9.5), -10.0, 10.0,  2.0];
        yield [self::createScale(-9.5, 9.5, 5), -10.0, 10.0,  5.0];

        yield [self::createScale(0.0, 95.0), 0.0, 100.0,  10.0];
        yield [self::createScale(-95, 95.0), -100.0, 100.0,  20.0];

        yield [self::createScale(0.0, 995.0), 0.0, 1200.0,  200.0];
        yield [self::createScale(-950.0, 950.0), -1000.0, 1000.0,  200.0];

        yield [self::createScale(0.0, 4.5), 0.0, 5.0,  0.5];
        yield [self::createScale(0.0, 4.6), 0.0, 5.0,  0.5];

        yield [self::createScale(0.0, 4.5), 0.0, 5.0,  0.5];
        yield [self::createScale(0.0, 4.5, 9), 0.0, 5.0,  0.5];
        yield [self::createScale(0.0, 4.5, 5), 0.0, 5.0,  1.0];

        yield [self::createScale(4.5, 0.0, 5), 0.0, 5.0,  1.0];
        yield [self::createScale(0.0, 0.0), 0.0, 1.0, 0.1];

        yield [self::createScale(0.0, 9.5, 0), 0.0, 10.0,  10.0];
    }

    public function testFixBounds(): void
    {
        $actual = self::createScale(1.0, 1.0);
        self::assertEqualsWithDelta(0.985, $actual->getLowerBound(), 0.01);
        self::assertEqualsWithDelta(1.015, $actual->getUpperBound(), 0.01);
        self::assertSame(0.005, $actual->getTickSpacing());
    }

    public function testLowerNegative(): void
    {
        $actual = self::createScale(-1.0, 100.0, 20);
        self::assertSame(-10.0, $actual->getLowerBound());
        self::assertSame(110.0, $actual->getUpperBound());
        self::assertSame(10.0, $actual->getTickSpacing());
    }

    public function testProperties(): void
    {
        $actual = self::createScale(0.0, 100.0, 20);
        self::assertSame(0.0, $actual->getLowerBound());
        self::assertSame(110.0, $actual->getUpperBound());
        self::assertSame(10.0, $actual->getTickSpacing());
    }

    #[DataProvider('getValues')]
    public function testScale(PdfBarScale $scale, float $lowerBound, float $upperBound, float $tickSpacing): void
    {
        self::assertSame($lowerBound, $scale->getLowerBound());
        self::assertSame($upperBound, $scale->getUpperBound());
        self::assertSame($tickSpacing, $scale->getTickSpacing());
    }

    public function testUpperLessLower(): void
    {
        $actual = self::createScale(100.0, 1.0, 20);
        self::assertSame(0.0, $actual->getLowerBound());
        self::assertSame(110.0, $actual->getUpperBound());
        self::assertSame(10.0, $actual->getTickSpacing());
    }

    public function testUpperNegative(): void
    {
        $actual = self::createScale(-100.0, -10.0);
        self::assertSame(-110.0, $actual->getLowerBound());
        self::assertSame(0.0, $actual->getUpperBound());
        self::assertSame(10.0, $actual->getTickSpacing());
    }

    public function testUpperZero(): void
    {
        $actual = self::createScale(-100.0, 0.0);
        self::assertSame(-120.0, $actual->getLowerBound());
        self::assertSame(0.0, $actual->getUpperBound());
        self::assertSame(20.0, $actual->getTickSpacing());
    }

    private static function createScale(float $lowerBound, float $upperBound, int $maxTicks = 10): PdfBarScale
    {
        return new PdfBarScale($lowerBound, $upperBound, $maxTicks);
    }
}
