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

use App\Enums\ImageExtension;
use Monolog\Test\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(ImageExtension::class)]
class ImageExtensionTest extends TestCase
{
    public static function getValues(): array
    {
        return [
            [ImageExtension::BMP, 'bmp'],
            [ImageExtension::GIF, 'gif'],
            [ImageExtension::JPEG, 'jpeg'],
            [ImageExtension::JPG, 'jpg'],
            [ImageExtension::PNG, 'png'],
            [ImageExtension::XBM, 'xbm'],
        ];
    }

    public function testCount(): void
    {
        self::assertCount(6, ImageExtension::cases());
    }

    public function testDefault(): void
    {
        $default = ImageExtension::getDefault();
        self::assertSame(ImageExtension::PNG, $default);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValues')]
    public function testValues(ImageExtension $imageExtension, string $expected): void
    {
        self::assertSame($expected, $imageExtension->value);
    }
}
