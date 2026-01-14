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

use App\Traits\ImageSizeTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ImageSizeTraitTest extends TestCase
{
    use ImageSizeTrait;

    public static function getSizes(): \Generator
    {
        yield ['', [0, 0]];
        yield [__DIR__ . '/../files/images/example.png', 124, 147];
        yield [__DIR__ . '/../files/images/example.jpg', 500, 477];
    }

    #[DataProvider('getSizes')]
    public function testImageSize(string $filename, int $width, int $height): void
    {
        $actual = $this->getImageSize($filename);
        self::assertSame($width, $actual->width);
        self::assertSame($height, $actual->height);
    }

    public function testImageSizeInvalid(): void
    {
        $actual = $this->getImageSize(__FILE__);
        self::assertSame(0, $actual->width);
        self::assertSame(0, $actual->height);
    }
}
