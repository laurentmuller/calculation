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

use App\Enums\Theme;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Unit test for the {@link Theme} enumeration.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ThemeTest extends TypeTestCase
{
    public function testCount(): void
    {
        self::assertCount(2, Theme::cases());
    }

    public function testCss(): void
    {
        self::assertEquals('js/vendor/bootstrap/css/bootstrap-dark.css', Theme::DARK->getCss());
        self::assertEquals('js/vendor/bootstrap/css/bootstrap-light.css', Theme::LIGHT->getCss());
    }

    public function testDefault(): void
    {
        $default = Theme::getDefault();
        self::assertEquals(Theme::LIGHT, $default);
    }

    public function testIcon(): void
    {
        self::assertEquals('fa-solid fa-moon', Theme::DARK->getIcon());
        self::assertEquals('fa-regular fa-sun', Theme::LIGHT->getIcon());
    }

    public function testLabel(): void
    {
        self::assertEquals('theme.dark.name', Theme::DARK->getReadable());
        self::assertEquals('theme.light.name', Theme::LIGHT->getReadable());
    }

    public function testSorted(): void
    {
        $expected = [
            Theme::LIGHT,
            Theme::DARK,
        ];
        $sorted = Theme::sorted();
        self::assertEquals($expected, $sorted);
    }

    public function testValue(): void
    {
        self::assertEquals('dark', Theme::DARK->value);
        self::assertEquals('light', Theme::LIGHT->value);
    }
}
