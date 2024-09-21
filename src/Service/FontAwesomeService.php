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
use App\Traits\LoggerTrait;
use App\Utils\FileUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service to get Font Awesome images.
 */
class FontAwesomeService
{
    use CacheKeyTrait;
    use LoggerTrait;

    public const ALIAS_FILE_NAME = 'aliases.json';
    public const SVG_EXTENSION = '.svg';

    private const SVG_REPLACE = '<svg fill="%s" ';
    private const SVG_SEARCH = '<svg ';
    private const TARGET_SIZE = 64;
    private const VIEW_BOX_PATTERN = '/viewBox="(\d+\s+){2}(?\'width\'\d+)\s+(?\'height\'\d+)"/mi';

    private ?\Imagick $imagick = null;
    private bool $imagickException = false;

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/fontawesome')]
        private readonly string $svgDirectory,
        private readonly FontAwesomeIconService $service,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __destruct()
    {
        $this->imagick?->clear();
        $this->imagick = null;
    }

    /**
     * Gets the icon aliases.
     *
     * @return array<string, string> the aliases where key is alias name and value is existing file
     */
    public function getAliases(): array
    {
        return $this->cache->get(
            $this->cleanKey(self::ALIAS_FILE_NAME),
            fn (): array => $this->loadAliases()
        );
    }

    /**
     * Gets a Font Awesome image.
     *
     * @param string      $relativePath the relative file path to the SVG directory.
     *                                  The SVG file extension (.svg) is added if not present.
     * @param string|null $color        the foreground color to apply or <code>null</code> for black color
     *
     * @return ?FontAwesomeImage the image, if found, <code>null</code> otherwise
     */
    public function getImage(string $relativePath, ?string $color = null): ?FontAwesomeImage
    {
        if (!FileUtils::isDir($this->svgDirectory)) {
            return null;
        }
        if ($this->imagickException || !$this->isSvgSupported()) {
            return null;
        }

        $relativePath = $this->normalizePath($relativePath);
        $path = FileUtils::buildPath($this->svgDirectory, $relativePath);
        if (!FileUtils::isFile($path)) {
            return null;
        }

        $color ??= 'black';
        $key = \sprintf('%s_%s', $path, $color);

        return $this->cache->get(
            $this->cleanKey($key),
            fn (ItemInterface $item, bool &$save): ?FontAwesomeImage => $this->loadImage($path, $color, $item, $save)
        );
    }

    /**
     * Gets a Font Awesome image from the given icon class.
     *
     * @param string      $icon  the icon class to get image for
     * @param string|null $color the foreground color to apply or <code>null</code> for black color
     *
     * @return ?FontAwesomeImage the image, if found, <code>null</code> otherwise
     */
    public function getImageFromIcon(string $icon, ?string $color = null): ?FontAwesomeImage
    {
        $path = $this->service->getIconPath($icon);
        if (null === $path) {
            return null;
        }

        return $this->getImage($path, $color);
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Gets the directory where SVG files as stored.
     */
    public function getSvgDirectory(): string
    {
        return $this->svgDirectory;
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
        $formats = \Imagick::queryFormats('SVG');

        return 0 !== \count($formats);
    }

    private function convert(string $content): FontAwesomeImage
    {
        $imagick = null;

        try {
            $imagick = $this->getImagick();

            $imagick->readImageBlob($content);
            $size = $this->getTargetSize($content);
            $imagick->resizeImage($size[0], $size[1], \Imagick::FILTER_LANCZOS, 1);
            $imagick->transparentPaintImage('white', 0.0, (float) \Imagick::getQuantum(), false);
            $imagick->setImageFormat('png24');
            $imageBlob = $imagick->getImageBlob();
            $resolution = (int) $imagick->getImageResolution()['x'];

            return new FontAwesomeImage($imageBlob, $size[0], $size[1], $resolution);
        } finally {
            $imagick?->clear();
        }
    }

    private function getImagick(): \Imagick
    {
        if (!$this->imagick instanceof \Imagick) {
            $this->imagick = new \Imagick();
        }

        return $this->imagick;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function getTargetSize(string $content): array
    {
        $result = \preg_match(self::VIEW_BOX_PATTERN, $content, $matches);
        if (1 !== $result || $matches['width'] === $matches['height']) {
            return [self::TARGET_SIZE, self::TARGET_SIZE];
        }

        $width = (int) $matches['width'];
        $height = (int) $matches['height'];
        if ($width > $height) {
            return [self::TARGET_SIZE, $this->round(self::TARGET_SIZE * $height, $width)];
        }

        return [$this->round(self::TARGET_SIZE * $width, $height), self::TARGET_SIZE];
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

        /** @psalm-var array<string, string> */
        return FileUtils::decodeJson($path);
    }

    private function loadImage(string $path, string $color, ItemInterface $item, bool &$save): ?FontAwesomeImage
    {
        $save = false;
        if (!$this->isSvgSupported()) {
            return null;
        }

        $content = \file_get_contents($path);
        if (!\is_string($content)) {
            $this->logError(\sprintf('Unable to read the file "%s".', $path));

            return null;
        }

        $content = $this->updateFillColor($content, $color);

        try {
            $image = $this->convert($content);
            $item->set($image);
            $save = true;

            return $image;
        } catch (\Exception $e) {
            if ($e instanceof \ImagickException) {
                $this->imagickException = true;
            } else {
                $this->logException($e, \sprintf('Unable to load image "%s".', $path));
            }

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

    private function round(float $dividend, float $divisor): int
    {
        return (int) \round($dividend / $divisor);
    }

    private function updateFillColor(string $content, string $color): string
    {
        return \str_replace(self::SVG_SEARCH, \sprintf(self::SVG_REPLACE, $color), $content);
    }
}
