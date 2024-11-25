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

namespace App\Tests\Twig;

use App\Enums\Theme;
use App\Service\ThemeService;
use App\Twig\ThemeExtension;
use PHPUnit\Framework\MockObject\Exception;

class ThemeExtensionTest extends IntegrationTestCase
{
    /**
     * @throws Exception
     */
    protected function getExtensions(): array
    {
        $service = $this->createMock(ThemeService::class);
        $service->method('getThemes')
            ->willReturn(Theme::sorted());
        $service->method('getTheme')
            ->willReturn(Theme::getDefault());
        $service->method('getThemeValue')
            ->willReturn(Theme::getDefault()->value);
        $service->method('isDarkTheme')
            ->willReturn(false);

        return [new ThemeExtension($service)];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/ThemeExtension';
    }
}
