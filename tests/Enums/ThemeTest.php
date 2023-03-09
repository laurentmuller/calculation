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

#[\PHPUnit\Framework\Attributes\CoversClass(Theme::class)]
class ThemeTest extends TypeTestCase
{
    public function testCount(): void
    {
        self::assertCount(2, Theme::cases());
    }

    public function testCss(): void
    {
        self::assertSame('js/vendor/bootstrap/css/bootstrap-dark.css', Theme::DARK->getCss());
        self::assertSame('js/vendor/bootstrap/css/bootstrap-light.css', Theme::LIGHT->getCss());
    }

    public function testDefault(): void
    {
        $default = Theme::getDefault();
        self::assertSame(Theme::LIGHT, $default);
    }

    public function testIcon(): void
    {
        self::assertSame('fa-solid fa-moon', Theme::DARK->getIcon());
        self::assertSame('fa-regular fa-sun', Theme::LIGHT->getIcon());
    }

    public function testLabel(): void
    {
        self::assertSame('theme.dark.name', Theme::DARK->getReadable());
        self::assertSame('theme.light.name', Theme::LIGHT->getReadable());
    }

    public function testSorted(): void
    {
        $expected = [
            Theme::LIGHT,
            Theme::DARK,
        ];
        $sorted = Theme::sorted();
        self::assertCount(2, $sorted);
        self::assertSame($expected, $sorted);
    }

    public function testSuccess(): void
    {
        self::assertSame('theme.dark.success', Theme::DARK->getSuccess());
        self::assertSame('theme.light.success', Theme::LIGHT->getSuccess());
    }

    public function testTitle(): void
    {
        self::assertSame('theme.dark.title', Theme::DARK->getTitle());
        self::assertSame('theme.light.title', Theme::LIGHT->getTitle());
    }

    public function testValue(): void
    {
        self::assertSame('dark', Theme::DARK->value);
        self::assertSame('light', Theme::LIGHT->value);
    }
}
