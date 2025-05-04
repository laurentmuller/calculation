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
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ThemeTest extends TestCase
{
    use TranslatorMockTrait;

    /**
     * @phpstan-return \Generator<int, array{Theme, string}>
     */
    public static function getHelps(): \Generator
    {
        yield [Theme::AUTO, 'theme.auto.help'];
        yield [Theme::DARK, 'theme.dark.help'];
        yield [Theme::LIGHT, 'theme.light.help'];
    }

    /**
     * @phpstan-return \Generator<int, array{Theme, string}>
     */
    public static function getIcons(): \Generator
    {
        yield [Theme::AUTO, 'fa-solid fa-circle-half-stroke'];
        yield [Theme::DARK, 'fa-regular fa-moon'];
        yield [Theme::LIGHT, 'fa-regular fa-sun'];
    }

    /**
     * @phpstan-return \Generator<int, array{Theme, string}>
     */
    public static function getLabels(): \Generator
    {
        yield [Theme::AUTO, 'theme.auto.name'];
        yield [Theme::DARK, 'theme.dark.name'];
        yield [Theme::LIGHT, 'theme.light.name'];
    }

    /**
     * @phpstan-return \Generator<int, array{Theme, string}>
     */
    public static function getSuccess(): \Generator
    {
        yield [Theme::AUTO, 'theme.auto.success'];
        yield [Theme::DARK, 'theme.dark.success'];
        yield [Theme::LIGHT, 'theme.light.success'];
    }

    /**
     * @phpstan-return \Generator<int, array{Theme, string}>
     */
    public static function getThumbnails(): \Generator
    {
        yield [Theme::AUTO, 'images/themes/theme_auto.png'];
        yield [Theme::DARK, 'images/themes/theme_dark.png'];
        yield [Theme::LIGHT, 'images/themes/theme_light.png'];
    }

    /**
     * @phpstan-return \Generator<int, array{Theme, string}>
     */
    public static function getTranslates(): \Generator
    {
        yield [Theme::AUTO, 'theme.auto.name'];
        yield [Theme::DARK, 'theme.dark.name'];
        yield [Theme::LIGHT, 'theme.light.name'];
    }

    /**
     * @phpstan-return \Generator<int, array{Theme, string}>
     */
    public static function getValues(): \Generator
    {
        yield [Theme::AUTO, 'auto'];
        yield [Theme::DARK, 'dark'];
        yield [Theme::LIGHT, 'light'];
    }

    public function testCount(): void
    {
        self::assertCount(3, Theme::cases());
    }

    public function testDefault(): void
    {
        $expected = Theme::AUTO;
        $actual = Theme::getDefault();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getHelps')]
    public function testHelp(Theme $theme, string $expected): void
    {
        $actual = $theme->getHelp();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getIcons')]
    public function testIcon(Theme $theme, string $expected): void
    {
        $actual = $theme->getIcon();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLabels')]
    public function testLabel(Theme $theme, string $expected): void
    {
        $actual = $theme->getReadable();
        self::assertSame($expected, $actual);
    }

    public function testSorted(): void
    {
        $expected = [
            Theme::LIGHT,
            Theme::DARK,
            Theme::AUTO,
        ];
        $actual = Theme::sorted();
        self::assertCount(3, $actual);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getSuccess')]
    public function testSuccess(Theme $theme, string $expected): void
    {
        $actual = $theme->getSuccess();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getThumbnails')]
    public function testThumbnail(Theme $theme, string $expected): void
    {
        $actual = $theme->getThumbnail();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getTranslates')]
    public function testTranslate(Theme $theme, string $expected): void
    {
        $translator = $this->createMockTranslator();
        $actual = $theme->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getValues')]
    public function testValue(Theme $theme, string $expected): void
    {
        $actual = $theme->value;
        self::assertSame($expected, $actual);
    }
}
