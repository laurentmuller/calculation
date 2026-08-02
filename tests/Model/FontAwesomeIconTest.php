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

use App\Enums\FontAwesomePath;
use App\Model\FontAwesomeIcon;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

final class FontAwesomeIconTest extends TestCase
{
    public function testAbsolutePath(): void
    {
        $expected = Path::join($this->getRootPath(), 'solid', 'user.svg');
        $icon = new FontAwesomeIcon(FontAwesomePath::SOLID, 'user');
        $actual = $icon->getAbsolutePath();
        self::assertSame($expected, $actual);
    }

    public function testAsHtml(): void
    {
        $icon = new FontAwesomeIcon(FontAwesomePath::SOLID, 'user');
        $actual = $icon->asHtml();
        self::assertSame('fa-solid fa-user', $actual);

        $actual = $icon->asHtml('fa-fw');
        self::assertSame('fa-solid fa-user fa-fw', $actual);
    }

    public function testKey(): void
    {
        $icon = new FontAwesomeIcon(FontAwesomePath::SOLID, 'user');
        $actual = $icon->getKey();
        self::assertSame('solid/user', $actual);
    }

    public function testToString(): void
    {
        $expected = Path::join($this->getRootPath(), 'solid', 'user.svg');
        $icon = new FontAwesomeIcon(FontAwesomePath::SOLID, 'user');
        $actual = (string) $icon;
        self::assertSame($expected, $actual);
    }

    private function getRootPath(): string
    {
        return Path::canonicalize(__DIR__ . '/../../resources/fontawesome');
    }
}
