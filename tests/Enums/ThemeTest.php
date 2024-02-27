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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(Theme::class)]
class ThemeTest extends TestCase
{
    public static function getHelps(): \Iterator
    {
        yield [Theme::AUTO, 'theme.auto.help'];
        yield [Theme::DARK, 'theme.dark.help'];
        yield [Theme::LIGHT, 'theme.light.help'];
    }

    public static function getIcons(): \Iterator
    {
        yield [Theme::AUTO, 'fa-solid fa-circle-half-stroke'];
        yield [Theme::DARK, 'fa-solid fa-moon'];
        yield [Theme::LIGHT, 'fa-solid fa-sun'];
    }

    public static function getIconsDark(): \Iterator
    {
        yield [Theme::AUTO, 'fa-solid fa-circle-half-stroke'];
        yield [Theme::DARK, 'fa-solid fa-moon'];
        yield [Theme::LIGHT, 'fa-solid fa-sun'];
    }

    public static function getIconsLight(): \Iterator
    {
        yield [Theme::AUTO, 'fa-solid fa-circle-half-stroke'];
        yield [Theme::DARK, 'fa-regular fa-moon'];
        yield [Theme::LIGHT, 'fa-regular fa-sun'];
    }

    public static function getLabels(): \Iterator
    {
        yield [Theme::AUTO, 'theme.auto.name'];
        yield [Theme::DARK, 'theme.dark.name'];
        yield [Theme::LIGHT, 'theme.light.name'];
    }

    public static function getSuccess(): \Iterator
    {
        yield [Theme::AUTO, 'theme.auto.success'];
        yield [Theme::DARK, 'theme.dark.success'];
        yield [Theme::LIGHT, 'theme.light.success'];
    }

    public static function getTitles(): \Iterator
    {
        yield [Theme::AUTO, 'theme.auto.title'];
        yield [Theme::DARK, 'theme.dark.title'];
        yield [Theme::LIGHT, 'theme.light.title'];
    }

    public static function getTranslates(): \Iterator
    {
        yield [Theme::AUTO, 'theme.auto.name'];
        yield [Theme::DARK, 'theme.dark.name'];
        yield [Theme::LIGHT, 'theme.light.name'];
    }

    public static function getValues(): \Iterator
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

    #[DataProvider('getIconsDark')]
    public function testIconDark(Theme $theme, string $expected): void
    {
        $actual = $theme->getIconDark();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getIconsLight')]
    public function testIconLight(Theme $theme, string $expected): void
    {
        $actual = $theme->getIconLight();
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

    #[DataProvider('getTitles')]
    public function testTitle(Theme $theme, string $expected): void
    {
        $actual = $theme->getTitle();
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    #[DataProvider('getTranslates')]
    public function testTranslate(Theme $theme, string $expected): void
    {
        $translator = $this->createTranslator();
        $actual = $theme->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getValues')]
    public function testValue(Theme $theme, string $expected): void
    {
        $actual = $theme->value;
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    private function createTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnArgument(0);

        return $translator;
    }
}
