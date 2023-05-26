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

namespace App\Twig;

use App\Enums\Theme;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for theme functions.
 */
class ThemeExtension extends AbstractExtension
{
    /**
     * The key name for selected theme cookie.
     */
    public const KEY_THEME = 'THEME';

    public function getFunctions(): array
    {
        return [
            new TwigFunction('theme', $this->getTheme(...)),
            new TwigFunction('themes', $this->getThemes(...)),
            new TwigFunction('theme_value', $this->getThemeValue(...)),
            new TwigFunction('is_dark_theme', $this->isDarkTheme(...)),
        ];
    }

    /**
     * Gets the selected theme.
     */
    public function getTheme(Request $request): Theme
    {
        $default = Theme::getDefault();
        $value = $request->cookies->get(self::KEY_THEME, $default->value);

        return Theme::tryFrom($value) ?? $default;
    }

    /**
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

    public function isDarkTheme(Request $request): bool
    {
        return Theme::DARK === $this->getTheme($request);
    }
}
