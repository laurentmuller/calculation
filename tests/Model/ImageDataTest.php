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

use App\Model\ImageData;
use PHPUnit\Framework\TestCase;

final class ImageDataTest extends TestCase
{
    public function testValidData(): void
    {
        $path = __DIR__ . '/../files/images/example.png';
        $content = \file_get_contents($path);
        self::assertIsString($content);
        $imageData = ImageData::instance($content);

        $actual = $imageData->getData();
        self::assertSame($content, $actual);

        $actual = $imageData->getFileType();
        self::assertSame('png', $actual);

        $actual = $imageData->getMimeType();
        self::assertSame('image/png', $actual);

        $actual = $imageData->getFileName();
        $expected = 'data://image/png;base64,' . \base64_encode($content);
        self::assertSame($expected, $actual);

        $actual = $imageData->getSize();
        self::assertIsArray($actual);
        self::assertSame(124, $actual[0]);
        self::assertSame(147, $actual[1]);
    }
}
