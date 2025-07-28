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

use App\Model\FontAwesomeImage;
use App\Traits\CacheKeyTrait;
use App\Utils\FileUtils;
use App\Utils\StringUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service to get Font Awesome images.
 */
class FontAwesomeImageService
{
    use CacheKeyTrait;

    /**
     * The JSON file containing aliases.
     */
    public const ALIAS_FILE_NAME = 'aliases.json';

    /**
     * The black color.
     */
    public const COLOR_BLACK = 'black';

    /**
     * The SVG file extension (including the dot character).
     */
    public const SVG_EXTENSION = '.svg';

    private const IMAGE_FORMAT = 'png24';
    private const SVG_PREFIX = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>';
    private const SVG_REPLACE = '<svg fill="%s" ';
    private const SVG_SEARCH = '<svg ';
    private const TARGET_SIZE = 64;
    private const TRANSPARENT_COLOR = 'white';
    private const VIEW_BOX_PATTERN = '/viewBox="(\d+\s+){2}(?\'width\'\d+)\s+(?\'height\'\d+)"/mi';

    private ?\Imagick $imagick = null;
    private bool $imagickException = false;

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/fontawesome')]
        private readonly string $svgDirectory,
        #[Target('calculation.fontawesome')]
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Gets the icon aliases.
     *
     * @return array<string, string> the aliases where the key is the alias name and the value is the existing file
     */
    public function getAliases(): array
    {
        return $this->cache->get('aliases_json', fn (): array => $this->loadAliases());
    }

    /**
     * Gets a Font Awesome image.
     *
     * @param string  $relativePath the relative file path to the SVG directory.
     *                              The SVG file extension (.svg) is added if not present.
     * @param ?string $color        the foreground color to apply or <code>null</code> for black color
     *
     * @return ?FontAwesomeImage the image, if found, <code>null</code> otherwise
     */
    public function getImage(string $relativePath, ?string $color = null): ?FontAwesomeImage
    {
        if (!$this->isCallable()) {
            return null;
        }

        $relativePath = $this->normalizePath($relativePath);
        $path = FileUtils::buildPath($this->svgDirectory, $relativePath);
        if (!FileUtils::isFile($path)) {
            return null;
        }

        $color ??= self::COLOR_BLACK;
        $key = $this->cleanKey(\sprintf('%s_%s', $relativePath, $color));

        return $this->cache->get(
            $key,
            fn (ItemInterface $item, bool &$save): ?FontAwesomeImage => $this->loadImage($path, $color, $item, $save)
        );
    }

    /**
     * Gets the directory where SVG files are stored.
     */
    public function getSvgDirectory(): string
    {
        return $this->svgDirectory;
    }

    /**
     * Gets a value indicating if images can be loaded.
     *
     * To allow loading images, the SVG directory must exist; the SVG format must be supported by the Imagick
     * library, and no Imagick exception has yet been raised.
     */
    public function isCallable(): bool
    {
        return $this->isSvgDirectory() && $this->isSvgSupported() && !$this->isImagickException();
    }

    /**
     * Returns a value indicating if an imagick exception has been raised.
     */
    public function isImagickException(): bool
    {
        return $this->imagickException;
    }

    /**
     * Gets a value indicating if the Imagick library supports the SVG format.
     */
    public function isSvgSupported(): bool
    {
        return $this->cache->get('svg_supported', static fn (): bool => 0 !== \count(\Imagick::queryFormats('SVG')));
    }

    private function convert(string $content): FontAwesomeImage
    {
        $imagick = $this->getImagick();

        try {
            $imagick->readImageBlob($content);
            $size = $this->getTargetSize($content);
            $imagick->resizeImage($size[0], $size[1], \Imagick::FILTER_LANCZOS, 1);
            $imagick->transparentPaintImage(self::TRANSPARENT_COLOR, 0.0, (float) \Imagick::getQuantum(), false);
            $imagick->setImageFormat(self::IMAGE_FORMAT);
            $imageBlob = $imagick->getImageBlob();
            $resolution = (int) $imagick->getImageResolution()['x'];

            return new FontAwesomeImage($imageBlob, $size[0], $size[1], $resolution);
        } finally {
            $imagick->clear();
        }
    }

    private function getImagick(): \Imagick
    {
        return $this->imagick ??= new \Imagick();
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function getTargetSize(string $content): array
    {
        $result = StringUtils::pregMatch(self::VIEW_BOX_PATTERN, $content, $matches);
        if (!$result || $matches['width'] === $matches['height']) {
            return [self::TARGET_SIZE, self::TARGET_SIZE];
        }

        $width = (int) $matches['width'];
        $height = (int) $matches['height'];
        if ($width > $height) {
            return [self::TARGET_SIZE, $this->roundSize(self::TARGET_SIZE * $height, $width)];
        }

        return [$this->roundSize(self::TARGET_SIZE * $width, $height), self::TARGET_SIZE];
    }

    private function isSvgDirectory(): bool
    {
        return $this->cache->get('svg_directory', fn (): bool => FileUtils::isDir($this->svgDirectory));
    }

    /**
     * @return array<string, string>
     */
    private function loadAliases(): array
    {
        $path = FileUtils::buildPath($this->svgDirectory, self::ALIAS_FILE_NAME);
        if (!FileUtils::exists($path)) {
            return [];
        }

        /** @phpstan-var array<string, string> */
        return FileUtils::decodeJson($path);
    }

    private function loadImage(string $path, string $color, ItemInterface $item, bool &$save): ?FontAwesomeImage
    {
        try {
            $save = false;
            $content = (string) \file_get_contents($path);
            $content = self::SVG_PREFIX . $this->replaceFillColor($content, $color);
            $image = $this->convert($content);
            $item->set($image);
            $save = true;

            return $image;
        } catch (\Exception $e) {
            $this->imagickException = $e instanceof \ImagickException;

            return null;
        }
    }

    private function normalizePath(string $path): string
    {
        if (!\str_ends_with($path, self::SVG_EXTENSION)) {
            $path .= self::SVG_EXTENSION;
        }
        $path = Path::normalize($path);
        $aliases = $this->getAliases();

        return $aliases[$path] ?? $path;
    }

    private function replaceFillColor(string $content, string $color): string
    {
        return \str_replace(self::SVG_SEARCH, \sprintf(self::SVG_REPLACE, $color), $content);
    }

    private function roundSize(float $dividend, float $divisor): int
    {
        return (int) \round($dividend / $divisor);
    }
}
