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

use App\Constants\CacheAttributes;
use App\Model\FontAwesomeImage;
use App\Model\ImageSize;
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
     * The default black color.
     */
    public const string BLACK_COLOR = 'black';

    /**
     * The SVG file extension (including the dot character).
     */
    public const string SVG_EXTENSION = '.svg';

    private const string IMAGE_FORMAT = 'png24';
    private const string SVG_PREFIX = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>';
    private const int TARGET_SIZE = 64;
    private const string TRANSPARENT_COLOR = 'white';
    private const string VIEW_BOX_PATTERN = '/viewBox="(\d+\s+){2}(?\'width\'\d+)\s+(?\'height\'\d+)"/mi';

    private ?\Imagick $imagick = null;
    private bool $imagickException = false;

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/fontawesome')]
        private readonly string $directory,
        #[Target(CacheAttributes::CACHE_FONT_AWESOME)]
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Gets the directory where SVG files are stored.
     */
    public function getDirectory(): string
    {
        return $this->directory;
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
        if (!$this->isAvailable()) {
            return null;
        }

        $relativePath = $this->normalizePath($relativePath);
        $path = Path::join($this->directory, $relativePath);
        if (!\is_file($path)) {
            return null;
        }

        $color ??= self::BLACK_COLOR;
        $key = $this->cleanKey(\sprintf('%s_%s', $relativePath, $color));

        return $this->cache->get(
            $key,
            fn (ItemInterface $item, bool &$save): ?FontAwesomeImage => $this->loadImage($path, $color, $save)
        );
    }

    /**
     * Gets a value indicating if images can be loaded.
     *
     * To allow loading images, the SVG directory must exist; the SVG format must be supported by the Imagick
     * library, and no Imagick exception has yet been raised.
     */
    public function isAvailable(): bool
    {
        return $this->isDirectory() && $this->isSvgSupported() && !$this->isImagickException();
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
            $imageSize = $this->getTargetSize($content);
            $imagick->resizeImage($imageSize->width, $imageSize->height, \Imagick::FILTER_LANCZOS, 1);
            $imagick->transparentPaintImage(self::TRANSPARENT_COLOR, 0.0, (float) \Imagick::getQuantum(), false);
            $imagick->setImageFormat(self::IMAGE_FORMAT);
            $imageBlob = $imagick->getImageBlob();
            $resolution = (int) $imagick->getImageResolution()['x'];

            return new FontAwesomeImage($imageBlob, $imageSize, $resolution);
        } finally {
            $imagick->clear();
        }
    }

    private function getImagick(): \Imagick
    {
        return $this->imagick ??= new \Imagick();
    }

    private function getTargetSize(string $content): ImageSize
    {
        $result = StringUtils::pregMatch(self::VIEW_BOX_PATTERN, $content, $matches);
        if (!$result || $matches['width'] === $matches['height']) {
            return ImageSize::instance(self::TARGET_SIZE, self::TARGET_SIZE);
        }
        $width = (int) $matches['width'];
        $height = (int) $matches['height'];

        return ImageSize::instance($width, $height)
            ->resize(self::TARGET_SIZE);
    }

    private function isDirectory(): bool
    {
        return $this->cache->get('svg_directory', fn (): bool => \is_dir($this->directory));
    }

    private function loadImage(string $path, string $color, bool &$save): ?FontAwesomeImage
    {
        try {
            $save = false;
            $content = (string) \file_get_contents($path);
            $content = $this->updateContent($content, $color);
            $image = $this->convert($content);
            $save = true;

            return $image;
        } catch (\Exception $e) {
            if (!$this->imagickException) {
                $this->imagickException = $e instanceof \ImagickException;
            }

            return null;
        }
    }

    private function normalizePath(string $path): string
    {
        if (!\str_ends_with($path, self::SVG_EXTENSION)) {
            $path .= self::SVG_EXTENSION;
        }

        return FileUtils::normalize($path);
    }

    private function updateContent(string $content, string $color): string
    {
        return self::SVG_PREFIX . \str_replace('currentColor', $color, $content);
    }
}
