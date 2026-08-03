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

namespace App\Tests\Service;

use App\Model\FontAwesomeIcon;
use App\Model\FontAwesomeImage;
use App\Service\FontAwesomeImageService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Filesystem\Path;

final class FontAwesomeImageServiceTest extends TestCase
{
    public function testImageEmpty(): void
    {
        // first time to throw imagick exception
        $icon = $this->createIcon('empty.svg');
        $service = $this->createService();
        $actual = $service->getImage($icon);
        self::assertNull($actual);
        // second time to test imagick exception
        $actual = $service->getImage($icon);
        self::assertNull($actual);
    }

    public function testImageNotExist(): void
    {
        $icon = $this->createIcon('fake-icon-not-found');
        $service = $this->createService();
        $actual = $service->getImage($icon);
        self::assertNull($actual);
    }

    public function testImageValidWithDifferentSize(): void
    {
        $icon = $this->createIcon('448x512.svg');
        $service = $this->createService();
        $actual = $service->getImage($icon);
        self::assertInstanceOf(FontAwesomeImage::class, $actual);
    }

    public function testImageValidWithSameSize(): void
    {
        $icon = $this->createIcon('512x512.svg');
        $service = $this->createService();
        $actual = $service->getImage($icon);
        self::assertInstanceOf(FontAwesomeImage::class, $actual);
    }

    private function createIcon(string $name): FontAwesomeIcon
    {
        $path = Path::join(__DIR__, '../files/images', $name);
        $icon = self::createStub(FontAwesomeIcon::class);
        $icon->method('getAbsolutePath')
            ->willReturn($path);

        return $icon;
    }

    private function createService(): FontAwesomeImageService
    {
        return new FontAwesomeImageService(new NullAdapter());
    }
}
