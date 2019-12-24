<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Twig;

use App\Form\ThemeType;
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
     * The theme service.
     *
     * @var ThemeService
     */
    private $service;

    /**
     * Constructor.
     *
     * @param ThemeService $service the theme service
     */
    public function __construct(ThemeService $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('theme_css', [$this, 'getThemeCss']),
            new TwigFunction('theme_name', [$this, 'getThemeName']),
            new TwigFunction('theme_default', [$this, 'isDefaultTheme']),
            new TwigFunction('theme_background', [$this, 'getThemeBackground']),
            new TwigFunction('theme_dark', [$this, 'isThemeDark']),
        ];
    }

    /**
     * Gets the theme background.
     *
     * @param Request the request
     */
    public function getThemeBackground(Request $request): string
    {
        // get background
        $background = $this->service->getThemeBackground($request);
        list($nav_foreground, $nav_background) = \explode(' ', $background);

        // check if exists
        if (!\in_array($nav_foreground, ThemeType::$FOREGROUND_CHOICES, true)) {
            return ThemeService::DEFAULT_BACKGROUND;
        }
        if (!\in_array($nav_background, ThemeType::$BACKGROUND_CHOICES, true)) {
            return ThemeService::DEFAULT_BACKGROUND;
        }

        return $background;
    }

    /**
     * Gets the theme CSS.
     *
     * @param Request the request
     */
    public function getThemeCss(Request $request): string
    {
        // get CSS
        $theme = $this->service->getCurrentTheme($request);
        if ($theme->exists()) {
            return $theme->getCss();
        }

        return ThemeService::DEFAULT_CSS;
    }

    /**
     * Gets the theme name.
     *
     * @param Request the request
     */
    public function getThemeName(Request $request): string
    {
        // get theme
        $theme = $this->service->getCurrentTheme($request);

        return $theme->getName();
    }

    /**
     * Returns if the selected theme is the default theme (Boostrap).
     *
     * @param Request the request
     */
    public function isDefaultTheme(Request $request): bool
    {
        // get theme
        $theme = $this->service->getCurrentTheme($request);

        return $theme->isDefault();
    }

    /**
     * Returns if the selected theme is dark.
     *
     * @param Request the request
     */
    public function isThemeDark(Request $request): bool
    {
        // get theme
        $theme = $this->service->getCurrentTheme($request);

        return $theme->isDark();
    }
}
