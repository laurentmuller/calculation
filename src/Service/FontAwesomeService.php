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
use App\Utils\StringUtils;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service to get Font Awesome images.
 */
class FontAwesomeService
{
    use CacheKeyTrait;
    use LoggerTrait;

    private const PATH_REPLACE = '<path style="fill:%s" ';
    private const PATH_SEARCH = '<path ';
    private const SVG_EXTENSION = '.svg';
    private const TARGET_SIZE = 64;
    private const VIEW_BOX_PATTERN = '/viewBox="(\d+\s+){2}(?\'width\'\d+)\s+(?\'height\'\d+)"/mi';

    private ?\Imagick $imagick = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%/vendor/fortawesome/font-awesome/svgs')]
        private readonly string $svgDirectory,
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
     * Gets a Font Awesome image.
     *
     * @param string      $relativePath the relative file path to the SVG directory.
     *                                  The SVG file extension (.svg) is added if not present.
     * @param string|null $color        the optional foreground color to apply
     *
     * @throws InvalidArgumentException
     */
    public function getImage(string $relativePath, ?string $color = null): ?FontAwesomeImage
    {
        if (!FileUtils::isDir($this->svgDirectory)) {
            return null;
        }

        if (!\str_ends_with($relativePath, self::SVG_EXTENSION)) {
            $relativePath .= self::SVG_EXTENSION;
        }
        $path = FileUtils::buildPath($this->svgDirectory, $relativePath);
        if (!FileUtils::isFile($path)) {
            return null;
        }

        $key = \sprintf('%s_%s', $path, $color ?? '');

        return $this->cache->get(
            $this->cleanKey($key),
            fn (ItemInterface $item, bool &$save): ?FontAwesomeImage => $this->loadImage($path, $color, $item, $save)
        );
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
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

    private function loadImage(string $path, ?string $color, ItemInterface $item, bool &$save): ?FontAwesomeImage
    {
        $save = false;
        $content = \file_get_contents($path);
        if (!\is_string($content)) {
            $this->logError(\sprintf('Unable to read the file "%s".', $path));

            return null;
        }

        if (StringUtils::isString($color)) {
            $content = $this->setFillColor($content, $color);
        }
        $size = $this->getTargetSize($content);

        $imagick = null;

        try {
            $imagick = $this->getImagick();
            $imagick->readImageBlob($content);
            $imagick->resizeImage($size[0], $size[1], \Imagick::FILTER_LANCZOS, 1);
            $imagick->setImageFormat('png24');
            $blob = $imagick->getImageBlob();
            $resolution = (int) $imagick->getImageResolution()['x'];
            $image = new FontAwesomeImage($blob, $size[0], $size[1], $resolution);
            $item->set($image);
            $save = true;

            return $image;
        } catch (\ImagickException $e) {
            $this->logException($e, \sprintf('Unable to load image "%s".', $path));

            return null;
        } finally {
            $imagick?->clear();
        }
    }

    private function round(float $dividend, float $divisor): int
    {
        return (int) \round($dividend / $divisor);
    }

    private function setFillColor(string $content, string $color): string
    {
        return \str_replace(self::PATH_SEARCH, \sprintf(self::PATH_REPLACE, $color), $content);
    }
}
