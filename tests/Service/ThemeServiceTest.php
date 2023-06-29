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

use App\Enums\Theme;
use App\Service\ThemeService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[\PHPUnit\Framework\Attributes\CoversClass(ThemeService::class)]
class ThemeServiceTest extends TestCase
{
    public static function getIsDarkTheme(): \Generator
    {
        yield [self::createRequest(), false];
        yield [self::createRequest('auto'), false];
        yield [self::createRequest('dark'), true];
        yield [self::createRequest('light'), false];
    }

    public static function getNextThemes(): \Generator
    {
        yield [Theme::LIGHT, Theme::DARK];
        yield [Theme::DARK, Theme::AUTO];
        yield [Theme::AUTO, Theme::LIGHT];
    }

    public static function getThemes(): \Generator
    {
        yield [self::createRequest(), Theme::AUTO];
        yield [self::createRequest('auto'), Theme::AUTO];
        yield [self::createRequest('dark'), Theme::DARK];
        yield [self::createRequest('light'), Theme::LIGHT];
    }

    public static function getThemeValues(): \Generator
    {
        yield [self::createRequest(), 'auto'];
        yield [self::createRequest('auto'), 'auto'];
        yield [self::createRequest('dark'), 'dark'];
        yield [self::createRequest('light'), 'light'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getNextThemes')]
    public function testGetNextTheme(Theme $actual, Theme $expected): void
    {
        $service = new ThemeService();
        $request = $this->createRequest($actual);
        $next = $service->getNextTheme($request);
        self::assertSame($expected, $next);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getThemes')]
    public function testGetTheme(Request $request, Theme $expected): void
    {
        $service = new ThemeService();
        $value = $service->getTheme($request);
        self::assertSame($expected, $value);
    }

    public function testGetThemes(): void
    {
        $service = new ThemeService();
        $themes = $service->getThemes();
        self::assertSame(Theme::sorted(), $themes);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getThemeValues')]
    public function testGetThemeValue(Request $request, string $expected): void
    {
        $service = new ThemeService();
        $value = $service->getThemeValue($request);
        self::assertSame($expected, $value);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getIsDarkTheme')]
    public function testIsDarkTheme(Request $request, bool $expected): void
    {
        $service = new ThemeService();
        $value = $service->isDarkTheme($request);
        self::assertSame($expected, $value);
    }

    private static function createRequest(Theme|string $value = null): Request
    {
        if ($value instanceof Theme) {
            $value = $value->value;
        }
        if ($value) {
            return new Request(cookies: ['THEME' => $value]);
        }

        return new Request();
    }
}
