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

use App\Model\ImageSize;
use PHPUnit\Framework\TestCase;

final class ImageSizeTest extends TestCase
{
    public function testInstance(): void
    {
        $imageSize = ImageSize::instance(0, 0);
        self::assertSame(0, $imageSize->width);
        self::assertSame(0, $imageSize->height);
        self::assertTrue($imageSize->isEmpty());
    }

    public function testResize(): void
    {
        $imageSize = ImageSize::instance(100, 100)
            ->resize(50);
        self::assertSame(50, $imageSize->width);
        self::assertSame(50, $imageSize->height);

        $imageSize = ImageSize::instance(200, 100)
            ->resize(50);
        self::assertSame(50, $imageSize->width);
        self::assertSame(25, $imageSize->height);

        $imageSize = ImageSize::instance(100, 200)
            ->resize(50);
        self::assertSame(25, $imageSize->width);
        self::assertSame(50, $imageSize->height);
    }
}
