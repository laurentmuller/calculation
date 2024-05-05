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

use App\Enums\ImageExtension;
use App\Enums\ImageSize;
use App\Traits\ImageSizeTrait;
use App\Traits\LoggerAwareTrait;
use App\Traits\TranslatorAwareTrait;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Service to resize images.
 */
class ImageResizer implements ServiceSubscriberInterface
{
    use ImageSizeTrait;
    use LoggerAwareTrait;
    use ServiceMethodsSubscriberTrait;
    use TranslatorAwareTrait;

    private ImagineInterface $imagine;

    public function __construct()
    {
        $this->imagine = new Imagine();
    }

    /**
     * Resize an image with the given size.
     *
     * @param string    $source the source image path
     * @param string    $target the target image path
     * @param ImageSize $size   the image size
     *
     * @return bool true on success, false on error, or if the size is not a positive value
     */
    public function resize(string $source, string $target, ImageSize $size): bool
    {
        try {
            $options = ['format' => ImageExtension::PNG->value];
            $newSize = $this->getNewSize($source, $size->value);
            $this->imagine->open($source)
                ->resize($newSize)
                ->save($target, $options);

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
        return $this->resize($source, $target, ImageSize::DEFAULT);
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
        return $this->resize($source, $target, ImageSize::MEDIUM);
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
        return $this->resize($source, $target, ImageSize::SMALL);
    }

    private function getNewSize(string $filename, float $size): Box
    {
        [$imageWidth, $imageHeight] = $this->getImageSize($filename);
        if (0 === $imageWidth || 0 === $imageHeight) {
            throw new \InvalidArgumentException("Unable to get image size for the file \"$filename\".");
        }
        $ratio = (float) $imageWidth / (float) $imageHeight;
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
