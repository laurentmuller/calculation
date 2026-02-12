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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Attribute\AsTwigFunction;

/**
 * Service to manage user theme.
 */
class ThemeService
{
    use CookieTrait;

    /** The key name for the selected theme cookie. */
    private const string KEY_THEME = 'THEME';

    public function __construct(
        #[Autowire('%cookie_path%')]
        private readonly string $cookiePath
    ) {
    }

    /**
     * Gets the selected theme from cookies.
     */
    #[AsTwigFunction(name: 'theme')]
    public function getTheme(Request $request): Theme
    {
        return $this->getCookieEnum($request, self::KEY_THEME, Theme::getDefault());
    }

    /**
     * Gets the sorted themes.
     *
     * @return Theme[]
     */
    #[AsTwigFunction(name: 'themes')]
    public function getThemes(): array
    {
        return Theme::sorted();
    }

    /**
     * Gets the selected theme value.
     */
    #[AsTwigFunction(name: 'theme_value')]
    public function getThemeValue(Request $request): string
    {
        return $this->getTheme($request)->value;
    }

    /**
     * Returns if the dark theme is selected.
     */
    #[AsTwigFunction(name: 'is_dark_theme')]
    public function isDarkTheme(Request $request): bool
    {
        return Theme::DARK === $this->getTheme($request);
    }

    /**
     * Save the given theme to cookies.
     */
    public function saveTheme(Response $response, Theme $theme): void
    {
        $this->updateCookie(response: $response, key: self::KEY_THEME, value: $theme, httpOnly: false);
    }

    #[\Override]
    protected function getCookiePath(): string
    {
        return $this->cookiePath;
    }
}
