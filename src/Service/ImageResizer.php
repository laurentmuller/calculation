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

use App\Interfaces\ImageExtensionInterface;
use App\Traits\LoggerAwareTrait;
use App\Traits\TranslatorAwareTrait;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service to resize images.
 */
class ImageResizer implements ImageExtensionInterface, ServiceSubscriberInterface
{
    use LoggerAwareTrait;
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

    /**
     * The default options.
     */
    private const DEFAULT_OPTIONS = [
        'format' => self::EXTENSION_PNG,
    ];

    private ?ImagineInterface $imagine = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        try {
            $this->imagine = new Imagine();
        } catch (\Exception $e) {
            $this->logException($e, $this->trans('user.image.failure'));
        }
    }

    /**
     * Resize an image with the given size.
     *
     * @param string $source the source image path
     * @param string $target the target image path
     * @param int    $size   the image size
     *
     * @return bool true on success, false on error or if the size is not a positive value
     *
     * @psalm-param ImageExtensionInterface::SIZE_* $size
     */
    public function resize(string $source, string $target, int $size): bool
    {
        if (null === $this->imagine) {
            return false;
        }

        try {
            $size = $this->getNewSize($source, $size);
            $this->imagine->open($source)
                ->resize($size)
                ->save($target, self::DEFAULT_OPTIONS);

            return true;
        } catch (\Exception $e) {
            $this->logException($e, $this->trans('user.image.failure'));

            return false;
        }
    }

    /**
     * Resize the given image with the default size (192 pixels).
     *
     * @param string $source the source image path
     * @param string $target the target image path
     *
     * @return bool true on success, false on error
     */
    public function resizeDefault(string $source, string $target): bool
    {
        return $this->resize($source, $target, self::SIZE_DEFAULT);
    }

    /**
     * Resize the given image with the medium size (96 pixels).
     *
     * @param string $source the source image path
     * @param string $target the target image path
     *
     * @return bool true on success, false on error
     */
    public function resizeMedium(string $source, string $target): bool
    {
        return $this->resize($source, $target, self::SIZE_MEDIUM);
    }

    /**
     * Resize the given image with the small size (32 pixels).
     *
     * @param string $source the source image path
     * @param string $target the target image path
     *
     * @return bool true on success, false on error
     */
    public function resizeSmall(string $source, string $target): bool
    {
        return $this->resize($source, $target, self::SIZE_SMALL);
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function getImageSize(string $filename): array
    {
        /** @psalm-var array{0: float, 1: float} $size */
        $size = \getimagesize($filename);

        return [$size[0], $size[1]];
    }

    private function getNewSize(string $filename, float $size): Box
    {
        [$imageWidth, $imageHeight] = $this->getImageSize($filename);
        $ratio = $imageWidth / $imageHeight;
        $width = $size;
        $height = $size;
        if ($width / $height > $ratio) {
            $width = $height * $ratio;
        } else {
            $height = $width / $ratio;
        }

        return new Box((int) $width, (int) $height);
    }
}
