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
    public static function getLabels(): array
    {
        return [
            [Theme::AUTO, 'theme.auto.name'],
            [Theme::DARK, 'theme.dark.name'],
            [Theme::LIGHT, 'theme.light.name'],
        ];
    }

    public static function getSuccess(): array
    {
        return [
            [Theme::AUTO, 'theme.auto.success'],
            [Theme::DARK, 'theme.dark.success'],
            [Theme::LIGHT, 'theme.light.success'],
        ];
    }

    public static function getTitles(): array
    {
        return [
            [Theme::AUTO, 'theme.auto.title'],
            [Theme::DARK, 'theme.dark.title'],
            [Theme::LIGHT, 'theme.light.title'],
        ];
    }

    public static function getTranslates(): array
    {
        return [
            [Theme::AUTO, 'theme.auto.name'],
            [Theme::DARK, 'theme.dark.name'],
            [Theme::LIGHT, 'theme.light.name'],
        ];
    }

    public static function getValues(): array
    {
        return [
            [Theme::AUTO, 'auto'],
            [Theme::DARK, 'dark'],
            [Theme::LIGHT, 'light'],
        ];
    }

    public function testCount(): void
    {
        self::assertCount(3, Theme::cases());
    }

    public function testDefault(): void
    {
        $default = Theme::getDefault();
        self::assertSame(Theme::AUTO, $default);
    }

    public function testIcon(): void
    {
        self::assertSame('fa-solid fa-circle-half-stroke', Theme::AUTO->getIcon());
        self::assertSame('fa-solid fa-moon', Theme::DARK->getIcon());
        self::assertSame('fa-solid fa-sun', Theme::LIGHT->getIcon());
    }

    #[DataProvider('getLabels')]
    public function testLabel(Theme $theme, string $expected): void
    {
        self::assertSame($expected, $theme->getReadable());
    }

    public function testSorted(): void
    {
        $expected = [
            Theme::AUTO,
            Theme::LIGHT,
            Theme::DARK,
        ];
        $sorted = Theme::sorted();
        self::assertCount(3, $sorted);
        self::assertSame($expected, $sorted);
    }

    #[DataProvider('getSuccess')]
    public function testSuccess(Theme $theme, string $expected): void
    {
        self::assertSame($expected, $theme->getSuccess());
    }

    #[DataProvider('getTitles')]
    public function testTitle(Theme $theme, string $expected): void
    {
        self::assertSame($expected, $theme->getTitle());
    }

    /**
     * @throws Exception
     */
    #[DataProvider('getTranslates')]
    public function testTranslate(Theme $theme, string $expected): void
    {
        $translator = $this->createTranslator();
        self::assertSame($expected, $theme->trans($translator));
    }

    #[DataProvider('getValues')]
    public function testValue(Theme $theme, string $expected): void
    {
        self::assertSame($expected, $theme->value);
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
