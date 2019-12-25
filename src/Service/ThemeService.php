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

namespace App\Service;

use App\Entity\Theme;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Service to manage Bootstrap themes.
 *
 * @author Laurent Muller
 */
class ThemeService
{
    /**
     * The default background class name for the navigation bar.
     */
    public const DEFAULT_BACKGROUND = 'navbar-dark bg-dark';

    /**
     * The default CSS theme path.
     */
    public const DEFAULT_CSS = 'js/vendor/twitter-bootstrap/css/bootstrap.css';

    /**
     * The default CSS theme dark (false).
     */
    public const DEFAULT_DARK = false;

    /**
     * The default theme description.
     */
    public const DEFAULT_DESCRIPTION = 'The default Bootstrap theme.';

    /**
     * The default theme name.
     */
    public const DEFAULT_NAME = 'Bootstrap';

    /**
     * The key name of the background style for the navigation bar.
     */
    public const KEY_BACKGROUND = 'THEME-BACKGROUND';

    /**
     * The key name for css theme.
     */
    public const KEY_CSS = 'THEME-CSS';

    /**
     * The key name for dark theme.
     */
    public const KEY_DARK = 'THEME-DARK';

    /**
     * The JSON themes file name.
     */
    private const JSON_FILE_NAME = 'themes.json';

    /**
     * The JSON themes file path.
     */
    private const JSON_FILE_PATH = '/public/js/vendor/themes/';

    /**
     * The key name to cache themes.
     */
    private const KEY_THEMES = 'themes';

    /**
     * The themes directory.
     */
    private const THEME_DIRECTORY = 'themes/';

    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * The default theme.
     *
     * @var Theme
     */
    private static $defaultTheme;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var RequestStack
     */
    private $stack;

    /**
     * Constructor.
     *
     * @param KernelInterface  $kernel the kernel to get themes file
     * @param RequestStack     $stack  the request stack to get current theme
     * @param AdapterInterface $cache  the cache used to save or retrieve themes
     */
    public function __construct(KernelInterface $kernel, RequestStack $stack, AdapterInterface $cache)
    {
        $this->kernel = $kernel;
        $this->cache = $cache;
        $this->stack = $stack;
    }

    /**
     * Finds a theme by the given css path.
     *
     * @param string|null $css the theme css to search for
     *
     * @return Theme the theme, if found, the default theme (Boostrap) if not found
     */
    public function findTheme(?string $css): Theme
    {
        if ($css && self::DEFAULT_CSS !== $css) {
            $themes = $this->getThemes();
            foreach ($themes as $theme) {
                if ($theme->getCss() === $css) {
                    return $theme;
                }
            }
        }

        return self::getDefaultTheme();
    }

    /**
     * Gets the current theme.
     *
     * @param Request the request
     *
     * @return Theme the current theme, if any; the default theme otherwise
     */
    public function getCurrentTheme(?Request $request = null): Theme
    {
        if ($request = $this->getRequest($request)) {
            $css = $request->cookies->get(self::KEY_CSS, self::DEFAULT_CSS);

            return $this->findTheme($css);
        }

        return self::getDefaultTheme();
    }

    /**
     * Gets the default theme (Boostrap).
     */
    public static function getDefaultTheme(): Theme
    {
        if (!self::$defaultTheme) {
            self::$defaultTheme = new Theme([
                'name' => self::DEFAULT_NAME,
                'description' => self::DEFAULT_DESCRIPTION,
                'css' => self::DEFAULT_CSS,
            ]);
        }

        return self::$defaultTheme;
    }

    /**
     * Gets the JSON themes file name.
     */
    public static function getFileName(): string
    {
        return self::JSON_FILE_NAME;
    }

    /**
     * Gets the theme background.
     *
     * @param Request the request
     */
    public function getThemeBackground(?Request $request = null): string
    {
        if ($request = $this->getRequest($request)) {
            return $request->cookies->get(self::KEY_BACKGROUND, self::DEFAULT_BACKGROUND);
        }

        return self::DEFAULT_BACKGROUND;
    }

    /**
     * Gets the themes.
     *
     * @return \App\Entity\Theme[]
     */
    public function getThemes(): array
    {
        // already cached?
        if (!$this->kernel->isDebug()) {
            $item = $this->cache->getItem(self::KEY_THEMES);
            if ($item->isHit()) {
                return $item->get();
            }
        }

        // add default theme (Boostrap)
        $themes = [self::DEFAULT_NAME => self::getDefaultTheme()];

        // get file
        $filename = $this->getThemesFile();
        if (!\file_exists($filename)) {
            return $themes;
        }

        // read file
        if (false === ($content = \file_get_contents($filename))) {
            return $themes;
        }

        // decode and check error
        $entries = \json_decode($content, true);
        if (JSON_ERROR_NONE !== \json_last_error() || empty($entries)) {
            return $themes;
        }

        // create themes
        foreach ($entries as $entry) {
            $theme = new Theme($entry);
            $themes[$theme->getName()] = $theme;
        }

        // cache themes
        if (!$this->kernel->isDebug()) {
            $item->set($themes);
            $item->expiresAfter(10 * 60); // 10 minutes
            $this->cache->save($item);
        }

        return $themes;
    }

    /**
     * Gets the JSON themes directory.
     */
    public static function getThemesDirectory(): string
    {
        return self::THEME_DIRECTORY;
    }

    /**
     * Gets the current request.
     *
     * @param Request $request an optional default request
     *
     * @return Request|null the request, if any; null otherwise
     */
    protected function getRequest(?Request $request = null): ?Request
    {
        if (!$request) {
            return $this->stack->getCurrentRequest();
        }

        return $request;
    }

    /**
     * Gets the JSON themes file path and name.
     */
    private function getThemesFile(): string
    {
        $rootDir = $this->kernel->getProjectDir();

        return $rootDir . self::JSON_FILE_PATH . self::JSON_FILE_NAME;
    }
}
