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
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ImageResizer implements ServiceSubscriberInterface, ImageExtensionInterface
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
     *
     * @throws \ReflectionException
     */
    public function __construct()
    {
        try {
            $this->imagine = new Imagine();
        } catch (\Exception $e) {
            $message = $this->trans('user.image.failure');
            $this->logException($e, $message);
        }
    }

    /**
     * Resize an image with the given size.
     *
     * @param string $source  the source image path
     * @param string $target  the target image path
     * @param int    $size    the image size
     * @param array  $options the options to use when saving image
     *
     * @return bool true on success, false on error or if the size is not a positive value
     *
     * @throws \ReflectionException
     */
    public function resize(string $source, string $target, int $size, array $options = []): bool
    {
        // check values?
        if (null === $this->imagine || $size <= 0) {
            return false;
        }

        try {
            $imageSize = (array) \getimagesize($source);
            $imageWidth = (float) $imageSize[0];
            $imageHeight = (float) $imageSize[1];
            $ratio = ($imageWidth / $imageHeight);

            $width = $size;
            $height = $size;
            if ($width / $height > $ratio) {
                $width = (int) ($height * $ratio);
            } else {
                $height = (int) ($width / $ratio);
            }
            $size = new Box($width, $height);
            $options = \array_merge(self::DEFAULT_OPTIONS, $options);

            // open, resize and save
            $this->imagine->open($source)
                ->resize($size)
                ->save($target, $options);

            return true;
        } catch (\Exception $e) {
            $message = $this->trans('user.image.failure');
            $this->logException($e, $message);

            return false;
        }
    }

    /**
     * Resize the given image with the default size (192 pixels).
     *
     * @param string $source  the source image path
     * @param string $target  the target image path
     * @param array  $options the options to use when saving image
     *
     * @return bool true on success, false on error
     *
     * @throws \ReflectionException
     */
    public function resizeDefault(string $source, string $target, array $options = []): bool
    {
        return $this->resize($source, $target, self::SIZE_DEFAULT, $options);
    }

    /**
     * Resize the given image with the medium size (96 pixels).
     *
     * @param string $source  the source image path
     * @param string $target  the target image path
     * @param array  $options the options to use when saving image
     *
     * @return bool true on success, false on error
     *
     * @throws \ReflectionException
     */
    public function resizeMedium(string $source, string $target, array $options = []): bool
    {
        return $this->resize($source, $target, self::SIZE_MEDIUM, $options);
    }

    /**
     * Resize the given image with the small size (32 pixels).
     *
     * @param string $source  the source image path
     * @param string $target  the target image path
     * @param array  $options the options to use when saving image
     *
     * @return bool true on success, false on error
     *
     * @throws \ReflectionException
     */
    public function resizeSmall(string $source, string $target, array $options = []): bool
    {
        return $this->resize($source, $target, self::SIZE_SMALL, $options);
    }
}
