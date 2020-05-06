<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Service;

use App\Interfaces\IImageExtension;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Psr\Log\LoggerInterface;

/**
 * Service to resize images.
 *
 * @author Laurent Muller
 */
class ImageResizer implements IImageExtension
{
    /**
     * The default options.
     *
     * @var array
     */
    private const DEFAULT_OPTIONS = [
        'format' => self::EXTENSION_PNG,
//         'png_compression_level' => 0,
//         'resolution-units' => ImageInterface::RESOLUTION_PIXELSPERINCH,
//         'resolution-x' => 200,
//         'resolution-y' => 200,
    ];

    /**
     * @var ImagineInterface
     */
    private $imagine;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        try {
            $this->imagine = new Imagine();
        } catch (\Exception $e) {
            $this->logError($e);
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
     * @return bool true on success, false on error or if the size is not positive
     */
    public function resize(string $source, string $target, int $size, array $options = []): bool
    {
        // check values?
        if (!$this->imagine || $size <= 0) {
            return  false;
        }

        try {
            list($imageWidth, $imageHeight) = \getimagesize($source);
            $ratio = $imageWidth / $imageHeight;

            $width = $size;
            $height = $size;
            if ($width / $height > $ratio) {
                $width = $height * $ratio;
            } else {
                $height = $width / $ratio;
            }

            // load and resize
            $image_size = new Box($width, $height);
            $image = $this->imagine->open($source);
            $image->resize($image_size);

            // save
            $options = \array_merge(self::DEFAULT_OPTIONS, $options);
            $image->save($target, $options);

            return true;
        } catch (\Exception $e) {
            $this->logError($e);

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

    /**
     * Logs the given exception.
     *
     * @param \Exception $e the exception to log
     */
    private function logError(\Exception $e): void
    {
        $context = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
        $this->logger->error($e->getMessage(), $context);
    }
}
