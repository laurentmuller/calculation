<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Twig;

use App\Form\User\ThemeType;
use App\Model\Theme;
use App\Service\ThemeService;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension to validate theme files.
 *
 * @author Laurent Muller
 */
final class ThemesExtension extends AbstractExtension
{
    /**
     * Constructor.
     *
     * @param ThemeService $service the theme service
     */
    public function __construct(private readonly ThemeService $service)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('theme_css', fn (Request $request): string => $this->getThemeCss($request)),
            new TwigFunction('theme_name', fn (Request $request): string => $this->getThemeName($request)),
            new TwigFunction('theme_default', fn (Request $request): bool => $this->isDefaultTheme($request)),
            new TwigFunction('theme_background', fn (Request $request): string => $this->getThemeBackground($request)),
            new TwigFunction('theme_dark', fn (Request $request): bool => $this->isDarkTheme($request)),
        ];
    }

    /**
     * Gets the theme background.
     *
     * @param Request $request the request
     */
    public function getThemeBackground(Request $request): string
    {
        // get background
        $background = $this->service->getThemeBackground($request);
        [$nav_foreground, $nav_background] = \explode(' ', $background);

        // check if exists
        if (!\in_array($nav_foreground, ThemeType::FOREGROUND_CHOICES, true)) {
            return ThemeService::DEFAULT_BACKGROUND;
        }
        if (!\in_array($nav_background, ThemeType::BACKGROUND_CHOICES, true)) {
            return ThemeService::DEFAULT_BACKGROUND;
        }

        return $background;
    }

    /**
     * Gets the theme CSS.
     *
     * @param Request $request the request
     */
    public function getThemeCss(Request $request): string
    {
        // get CSS
        $theme = $this->getCurrentTheme($request);
        if ($theme->exists()) {
            return $theme->getCss();
        }

        return ThemeService::DEFAULT_CSS;
    }

    /**
     * Gets the theme name.
     *
     * @param Request $request the request
     */
    public function getThemeName(Request $request): string
    {
        return $this->getCurrentTheme($request)->getName();
    }

    /**
     * Returns if the selected theme is dark.
     *
     * @param Request $request the request
     */
    public function isDarkTheme(Request $request): bool
    {
        return $this->service->isDarkTheme($request);
    }

    /**
     * Returns if the selected theme is the default theme (Boostrap).
     *
     * @param Request $request the request
     */
    public function isDefaultTheme(Request $request): bool
    {
        return $this->getCurrentTheme($request)->isDefault();
    }

    /**
     * Gets the current theme.
     *
     * @param Request|null $request the request
     *
     * @return Theme the current theme, if any; the default theme otherwise
     */
    private function getCurrentTheme(?Request $request = null): Theme
    {
        return $this->service->getCurrentTheme($request);
    }
}
