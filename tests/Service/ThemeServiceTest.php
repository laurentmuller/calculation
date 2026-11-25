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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ThemeServiceTest extends TestCase
{
    public static function getThemes(): \Generator
    {
        yield [self::createRequest(), Theme::DEFAULT];
        yield [self::createRequest('auto'), Theme::AUTO];
        yield [self::createRequest('dark'), Theme::DARK];
        yield [self::createRequest('light'), Theme::LIGHT];
    }

    #[DataProvider('getThemes')]
    public function testGetTheme(Request $request, Theme $expected): void
    {
        $service = new ThemeService('/');
        $value = $service->getTheme($request);
        self::assertSame($expected, $value);
    }

    public function testSaveTheme(): void
    {
        $path = '/';
        $theme = Theme::DARK;
        $service = new ThemeService('/');
        $response = new Response();
        $service->saveTheme($response, $theme);

        $cookies = $response->headers->getCookies();
        self::assertCount(1, $cookies);

        $cookie = $cookies[0];
        self::assertSame('THEME', $cookie->getName());
        self::assertSame($theme->value, $cookie->getValue());
        self::assertSame($path, $cookie->getPath());
        self::assertFalse($cookie->isHttpOnly());
    }

    private static function createRequest(Theme|string|null $value = null): Request
    {
        if ($value instanceof Theme) {
            $value = $value->value;
        }
        if (null !== $value) {
            return new Request(cookies: ['THEME' => $value]);
        }

        return new Request();
    }
}
