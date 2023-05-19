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
    private const KEY_THEME = 'THEME';

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('theme', $this->getTheme(...)),
            new TwigFunction('theme_value', $this->getThemeValue(...)),
        ];
    }

    /**
     * Gets the selected theme.
     */
    private function getTheme(Request $request): Theme
    {
        $default = Theme::getDefault();
        $value = $request->cookies->get(self::KEY_THEME, $default->value);

        return Theme::tryFrom($value) ?? $default;
    }

    /**
     * Returns the selected theme value.
     */
    private function getThemeValue(Request $request): string
    {
        return $this->getTheme($request)->value;
    }
}
