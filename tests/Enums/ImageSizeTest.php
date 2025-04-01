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

namespace App\Tests\Enums;

use App\Enums\ImageSize;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ImageSizeTest extends TestCase
{
    /**
     * @psalm-return \Generator<int, array{ImageSize, int}>
     */
    public static function getValues(): \Generator
    {
        yield [ImageSize::DEFAULT, 192];
        yield [ImageSize::MEDIUM, 96];
        yield [ImageSize::SMALL, 32];
    }

    public function testCount(): void
    {
        self::assertCount(3, ImageSize::cases());
    }

    public function testDefault(): void
    {
        $expected = ImageSize::DEFAULT;
        $actual = ImageSize::getDefault();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getValues')]
    public function testValue(ImageSize $imageSize, int $expected): void
    {
        $actual = $imageSize->value;
        self::assertSame($expected, $actual);
    }
}
