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

namespace App\Service;

use App\Model\Theme;
use App\Traits\CacheTrait;
use App\Util\FileUtils;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service to manage Bootstrap themes.
 *
 * @author Laurent Muller
 */
class ThemeService
{
    use CacheTrait;

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
    public const KEY_BACKGROUND = 'THEME_BACKGROUND';

    /**
     * The key name for css theme.
     */
    public const KEY_CSS = 'THEME_CSS';

    /**
     * The key name for dark theme.
     */
    public const KEY_DARK = 'THEME_DARK';

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
     * The theme's directory.
     */
    private const THEME_DIRECTORY = 'themes/';

    private static ?Theme $defaultTheme = null;

    private string $projectDir;

    private RequestStack $stack;

    /**
     * Constructor.
     */
    public function __construct(RequestStack $stack, CacheItemPoolInterface $adapter, string $projectDir, bool $isDebug)
    {
        $this->stack = $stack;
        $this->projectDir = $projectDir;
        if (!$isDebug) {
            $this->setAdapter($adapter);
        }
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
     * @param Request $request the optional request
     *
     * @return Theme the current theme, if any; the default theme otherwise
     */
    public function getCurrentTheme(?Request $request = null): Theme
    {
        if (null !== ($request = $this->getRequest($request))) {
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
        if (null === self::$defaultTheme) {
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
     * @param Request $request the request
     */
    public function getThemeBackground(?Request $request = null): string
    {
        if (null !== ($request = $this->getRequest($request))) {
            return $request->cookies->get(self::KEY_BACKGROUND, self::DEFAULT_BACKGROUND);
        }

        return self::DEFAULT_BACKGROUND;
    }

    /**
     * Gets the themes.
     *
     * @return \App\Model\Theme[]
     */
    public function getThemes(): array
    {
        // already cached?
        /** @var Theme[]|null $themes */
        $themes = $this->getCacheValue(self::KEY_THEMES);
        if (null !== $themes) {
            return $themes;
        }

        // add default theme (Boostrap)
        $themes = [self::DEFAULT_NAME => self::getDefaultTheme()];

        // get file
        $filename = $this->getThemesFile();
        if (!FileUtils::exists($filename)) {
            return $themes;
        }

        // read file
        if (false === ($content = \file_get_contents($filename))) {
            return $themes;
        }

        // decode and check error
        /** @var array<array{name: string, description: string, css: string}>|null $entries */
        $entries = \json_decode($content, true);
        if (\JSON_ERROR_NONE !== \json_last_error() || empty($entries)) {
            return $themes;
        }

        // create themes
        foreach ($entries as $entry) {
            $theme = new Theme($entry);
            $themes[$theme->getName()] = $theme;
        }

        // cache themes
        $this->setCacheValue(self::KEY_THEMES, $themes);

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
     * Returns if the current theme is a dark theme.
     *
     * @param Request $request the optional request
     *
     * @return bool true if dark; false otherwise
     */
    public function isDarkTheme(?Request $request = null): bool
    {
        return $this->getCurrentTheme($request)->isDark();
    }

    /**
     * Gets the current request.
     *
     * @param Request $request the optional request
     *
     * @return Request|null the request, if any; null otherwise
     */
    private function getRequest(?Request $request = null): ?Request
    {
        return $request ?? $this->stack->getCurrentRequest();
    }

    /**
     * Gets the JSON themes file path and name.
     */
    private function getThemesFile(): string
    {
        return $this->projectDir . self::JSON_FILE_PATH . self::JSON_FILE_NAME;
    }
}
