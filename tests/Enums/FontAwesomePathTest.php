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

use App\Enums\FontAwesomePath;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

final class FontAwesomePathTest extends TestCase
{
    public function testAsHtml(): void
    {
        $actual = FontAwesomePath::SOLID->asHtml('user');
        self::assertSame('fa-solid fa-user', $actual);

        $actual = FontAwesomePath::SOLID->asHtml('user', 'fa-fw');
        self::assertSame('fa-solid fa-user fa-fw', $actual);
    }

    public function testIconPath(): void
    {
        $expected = Path::join($this->getRootPath(), 'solid', 'user.svg');
        $actual = FontAwesomePath::SOLID->getIconPath('user');
        self::assertSame($expected, $actual);
    }

    public function testPath(): void
    {
        $expected = Path::join($this->getRootPath(), 'solid');
        $actual = FontAwesomePath::SOLID->getPath();
        self::assertSame($expected, $actual);
    }

    public function testRootPath(): void
    {
        $expected = $this->getRootPath();
        $actual = FontAwesomePath::getRootPath();
        self::assertSame($expected, $actual);
    }

    private function getRootPath(): string
    {
        return Path::canonicalize(__DIR__ . '/../../resources/fontawesome');
    }
}
