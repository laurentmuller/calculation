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
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(ImageSizeTrait::class)]
class ImageSizeTraitTest extends TestCase
{
    use ImageSizeTrait;

    public static function getSizes(): \Iterator
    {
        yield ['', [0, 0]];
        yield [__DIR__ . '/../Data/android.png', [124, 147]];
        yield [__DIR__ . '/../Data/bibi.jpg', [360, 308]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getSizes')]
    public function testSize(string $filename, array $expected): void
    {
        $actual = $this->getImageSize($filename);
        self::assertSame($expected, $actual);
    }
}
