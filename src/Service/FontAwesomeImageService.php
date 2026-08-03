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
use App\Model\FontAwesomeIcon;
use App\Model\FontAwesomeImage;
use App\Model\ImageSize;
use App\Traits\CacheKeyTrait;
use App\Utils\StringUtils;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service to convert SVG Font Awesome icons to images.
 */
class FontAwesomeImageService
{
    use CacheKeyTrait;

    /** The default foreground color. */
    public const string BLACK_COLOR = 'black';

    /** The SVG file extension (including the dot character). */
    public const string SVG_EXTENSION = '.svg';

    private const string IMAGE_FORMAT = 'png24';
    private const int RESOLUTION = 96;
    private const string SVG_PREFIX = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>';
    private const int TARGET_SIZE = 64;
    private const string TRANSPARENT_COLOR = 'white';
    private const string VIEW_BOX_PATTERN = '/viewBox="(\d+\s+){2}(?\'width\'\d+)\s+(?\'height\'\d+)"/mi';

    private ?\Imagick $imagick = null;
    private bool $imagickException = false;

    public function __construct(
        #[Target(CacheAttributes::CACHE_FONT_AWESOME)]
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Gets a Font Awesome image.
     *
     * @param FontAwesomeIcon $icon  the icon to get image for
     * @param ?string         $color the foreground color to apply or <code>null</code> for black color
     *
     * @return ?FontAwesomeImage the image, if found, <code>null</code> otherwise
     */
    public function getImage(FontAwesomeIcon $icon, ?string $color = null): ?FontAwesomeImage
    {
        if ($this->imagickException) {
            return null;
        }

        $path = $icon->getAbsolutePath();
        if (!\is_file($path)) {
            return null;
        }

        $color ??= self::BLACK_COLOR;
        $key = $this->cleanKey(\sprintf('%s_%s', $icon->getKey(), $color));

        return $this->cache->get(
            $key,
            fn (ItemInterface $item, bool &$save): ?FontAwesomeImage => $this->loadImage($path, $color, $save)
        );
    }

    private function convert(string $content): FontAwesomeImage
    {
        $imagick = $this->getImagick();

        try {
            $imagick->setResolution(self::RESOLUTION, self::RESOLUTION);
            $imagick->readImageBlob($content);

            $size = $this->getTargetSize($content);
            $imagick->resizeImage($size->width, $size->height, \Imagick::FILTER_LANCZOS, 1);

            $imagick->transparentPaintImage(self::TRANSPARENT_COLOR, 0.0, (float) \Imagick::getQuantum(), false);
            $imagick->setImageFormat(self::IMAGE_FORMAT);
            $imageBlob = $imagick->getImageBlob();

            return new FontAwesomeImage($imageBlob, $size, self::RESOLUTION);
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
            $this->imagickException = $this->imagickException || $e instanceof \ImagickException;

            return null;
        }
    }

    private function updateContent(string $content, string $color): string
    {
        return self::SVG_PREFIX . \str_replace('currentColor', $color, $content);
    }
}
