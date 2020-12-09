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

use App\Interfaces\ImageExtensionInterface;
use App\Traits\LoggerTrait;
use App\Traits\TranslatorTrait;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to resize images.
 *
 * @author Laurent Muller
 */
class ImageResizer implements ImageExtensionInterface
{
    use LoggerTrait;
    use TranslatorTrait;

    /**
     * The default options.
     */
    private const DEFAULT_OPTIONS = [
        'format' => self::EXTENSION_PNG,
    ];

    /**
     * @var ImagineInterface
     */
    private $imagine;

    /**
     * Constructor.
     */
    public function __construct(LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->logger = $logger;
        $this->translator = $translator;

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
     */
    public function resize(string $source, string $target, int $size, array $options = []): bool
    {
        // check values?
        if (null === $this->imagine || $size <= 0) {
            return  false;
        }

        try {
            [$imageWidth, $imageHeight] = \getimagesize($source);
            $ratio = $imageWidth / $imageHeight;

            $width = $size;
            $height = $size;
            if ($width / $height > $ratio) {
                $width = $height * $ratio;
            } else {
                $height = $width / $ratio;
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
     */
    public function resizeSmall(string $source, string $target, array $options = []): bool
    {
        return $this->resize($source, $target, self::SIZE_SMALL, $options);
    }
}
