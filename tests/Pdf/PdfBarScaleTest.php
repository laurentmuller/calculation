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
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(PdfBarScale::class)]
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

    #[\PHPUnit\Framework\Attributes\DataProvider('getValues')]
    public function testScale(PdfBarScale $scale, float $lowerBound, float $upperBound, float $tickSpacing): void
    {
        self::assertSame($lowerBound, $scale->getLowerBound());
        self::assertSame($upperBound, $scale->getUpperBound());
        self::assertSame($tickSpacing, $scale->getTickSpacing());
    }

    private static function createScale(float $lowerBound, float $upperBound, int $maxTicks = 10): PdfBarScale
    {
        return new PdfBarScale($lowerBound, $upperBound, $maxTicks);
    }
}
