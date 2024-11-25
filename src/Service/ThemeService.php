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

namespace App\Service;

use App\Enums\Theme;
use App\Traits\CookieTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service to manage user theme.
 */
class ThemeService
{
    use CookieTrait;

    /**
     * The key name for the selected theme cookie.
     */
    private const KEY_THEME = 'THEME';

    /**
     * Gets the selected theme from cookies.
     */
    public function getTheme(Request $request): Theme
    {
        return $this->getCookieEnum($request, self::KEY_THEME, Theme::getDefault());
    }

    /**
     * Gets the sorted themes.
     *
     * @return Theme[]
     */
    public function getThemes(): array
    {
        return Theme::sorted();
    }

    /**
     * Returns the selected theme value.
     */
    public function getThemeValue(Request $request): string
    {
        return $this->getTheme($request)->value;
    }

    /**
     * Returns if the dark theme is selected.
     */
    public function isDarkTheme(Request $request): bool
    {
        return Theme::DARK === $this->getTheme($request);
    }

    /**
     * Save the given theme to cookies.
     */
    public function saveTheme(Response $response, string $path, Theme $theme): void
    {
        $this->updateCookie(response: $response, key: self::KEY_THEME, value: $theme, path: $path, httpOnly: false);
    }
}
