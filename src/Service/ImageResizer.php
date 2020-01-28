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
use App\Traits\MathTrait;
use App\Utils\GifFrameExtractor;
use App\Utils\ImageHandler;

/**
 * Class to resize an image.
 *
 * @author Laurent Muller
 */
class ImageResizer implements IImageExtension
{
    use MathTrait;

    /**
     * Gets image informations.
     *
     * Returns a standard class with the following properties:
     * <ul>
     * <li><code>width</code>: The image width.</li>
     * <li><code>height</code>: The image height.</li>
     * <li><code>ratio</code>: The image ratio (<code>width / height</code>) or <code>false</code> if image height or the image width are equal to 0.</li>
     * <li><code>dirname</code>: The directory name.</li>
     * <li><code>basename</code>: The base name.</li>
     * <li><code>filename</code>: The file name.</li>
     * <li><code>extension</code>: The file extension.</li>
     * </ul>
     *
     * @param string $imagePath the full image path
     */
    public static function getImageInfo(string $imagePath): \stdClass
    {
        // file parts
        $result = (object) \pathinfo($imagePath);

        // size
        list($result->width, $result->height) = \getimagesize($imagePath);

        // ratio
        $result->ratio = (empty($result->height) || empty($result->width)) ? false : $result->width / $result->height;

        return $result;
    }

    /**
     * Gets image resolution.
     *
     * @param string $imagePath the full image path
     * @param int    $default   the default resolution
     *
     * @return int the image resolution, if applicable, the default resolution otherwise
     */
    public static function getImageResolution(string $imagePath, int $default = self::DEFAULT_RESOLUTION): int
    {
        $result = $default;
        if (\function_exists('imageresolution')) {
            $ext = \strtolower(\pathinfo($imagePath, PATHINFO_EXTENSION));
            switch ($ext) {
                case self::EXTENSION_BMP:
                    $image_src = \imagecreatefrombmp($imagePath);
                    break;
                case self::EXTENSION_GIF:
                    $image_src = \imagecreatefromgif($imagePath);
                    break;
                case self::EXTENSION_JPEG:
                case self::EXTENSION_JPG:
                    $image_src = \imagecreatefromjpeg($imagePath);
                    break;
                case self::EXTENSION_PNG:
                    $image_src = \imagecreatefrompng($imagePath);
                    break;
                case self::EXTENSION_XBM:
                    $image_src = \imagecreatefromxbm($imagePath);
                    break;
                default:
                    $image_src = null;
                    break;
            }
            if ($image_src) {
                if ($resolutions = \imageresolution($image_src)) {
                    $result = $resolutions[0];
                }
                \imagedestroy($image_src);
            }
        }

        return $result;
    }

    /**
     * Resize an image.
     *
     * @param string $source    the source file
     * @param string $target    the target file
     * @param int    $height    the new height to resize the image to
     * @param int    $width     the new width to resize the image to
     * @param string $sourceExt the source file extension or null to use the source file extension
     * @param string $targetExt the target file extension or null to use the source extension
     * @param bool   $square    true to create a square image, false to keep the ratio
     *
     * @return bool true if the image is resized is successull; false if fail
     */
    public function resize(string $source, string $target, int $height = 0, int $width = 0, ?string $sourceExt = null, ?string $targetExt = null, bool $square = false): bool
    {
        // get image informations
        $info = self::getImageInfo($source);

        // check ratio
        if (false === $info->ratio) {
            return false;
        }

        // resize dimensions are bigger than original image, stop processing
        $src_width = $info->width;
        $src_height = $info->height;
        if ($width > $src_width && $height > $src_height) {
            return false;
        }

        // set sizes
        $dest_height = $height;
        $dest_width = $width;
        if ($height > 0) {
            $width = (int) \round($height * $info->ratio);
            $dest_width = $square ? $height : $width;
        } elseif ($width > 0) {
            $height = (int) \round($width / $info->ratio);
            $dest_height = $square ? $width : $height;
        }

        // offsets
        $dest_x = (int) \round(($dest_width - $width) / 2);
        $dest_y = (int) \round(($dest_height - $height) / 2);

        // create image destination
        if (!$image_dest = ImageHandler::fromTrueColor($dest_width, $dest_height)) {
            return false;
        }

        // load image sourceTwigColumn
        $sourceExt = $sourceExt ?: $info->extension;
        if (!$image_src = $this->loadImage($source, $sourceExt)) {
            return false;
        }

        //if ($square || $this->mustFillImage($sourceExt)) {
        $color = $image_dest->allocateAlpha();
        $image_dest->transparent($color);
        $image_dest->fill($color);
        $image_dest->alphaBlending(false);
        $image_dest->saveAlpha(true);
        //}

        // copy and resize
        if (!$image_src->copyResampled($image_dest, $dest_x, $dest_y, 0, 0, $width, $height, $src_width, $src_height)) {
            return false;
        }
//         if (!\imagecopyresampled($image_dest, $image_src, $dest_x, $dest_y, 0, 0, $width, $height, $src_width, $src_height)) {
//             return false;
//         }

        // save
        $targetExt = $targetExt ?: $sourceExt;

        return $this->saveImage($image_dest, $target, $targetExt);
    }

//     /**
//      * Finds the first unused color.
//      *
//      * @param array $colors the image colors
//      *
//      * @return int an unused color or -1 if none
//      */
//     private function findUnusedColor(array $colors): int
//     {
//         for ($i = 0; $i < 0xFFFFFF; ++$i) {
//             if (!\in_array($i, $colors, true)) {
//                 return $i;
//             }
//         }

//         return -1;
//     }

//     /**
//      * Gets all used colors of the given image.
//      *
//      * @param resource $image  the image resource
//      * @param int      $width  the image width
//      * @param int      $height the image height
//      *
//      * @return array the used colors
//      */
//     private function getImageColors($image, $width, $height): array
//     {
//         $result = [];
//         for ($i = 0; $i < $width; ++$i) {
//             for ($j = 0; $j < $height; ++$j) {
//                 $rgb = \imagecolorat($image, $i, $j);
//                 $result[$rgb] = $rgb;
//             }
//         }

//         return $result;
//     }

    /**
     * Gets the GIF image.
     *
     * @param string $fileName the path to the GIF image
     *
     * @return ImageHandler|bool an image resource identifier on success, false on errors
     */
    private function imagecreatefromgif(string $fileName)
    {
        // see https://blog.lifetimecode.com/2015/12/06/how-to-resize-animated-gif-withou/
        if (GifFrameExtractor::isAnimatedGif($fileName)) {
            // replace image with the first frame
            $extractor = new GifFrameExtractor();
            $frames = $extractor->extract($fileName);
            $image = $frames[0]['image'];
            $result = \imagegif($image, $fileName);
            $extractor->closeFile();

            if (!$result) {
                return false;
            }
        }

        return ImageHandler::fromGif($fileName);
    }

    /**
     * Loads an image.
     *
     * @param string $file the path to load the image from
     * @param string $ext  the file extension or null to take the extension from the file
     *
     * @return ImageHandler|null an image handler on success; null on errors
     */
    private function loadImage(string $file, ?string $ext)
    {
        $ext = empty($ext) ? self::getImageInfo($file)->extension : $ext;
        switch (\strtolower($ext)) {
            case self::EXTENSION_BMP:
                return ImageHandler::fromBmp($file);

            case self::EXTENSION_GIF:
                return $this->imagecreatefromgif($file);

            case self::EXTENSION_JPEG:
            case self::EXTENSION_JPG:
                return ImageHandler::fromJpeg($file);

            case self::EXTENSION_PNG:
                return ImageHandler::fromPng($file);

            case self::EXTENSION_XBM:
                return ImageHandler::fromXbm($file);

            default:
                // format not supported
                return null;
        }
    }

    /**
     * Returns if the given image must filled.
     *
     * @param string $ext the file extension
     *
     * @return bool true if must filled
     */
    private function mustFillImage(string $ext): bool
    {
        switch (\strtolower($ext)) {
            case self::EXTENSION_GIF:
            case self::EXTENSION_PNG:
            // case self::EXTENSION_JPG:
            // case self::EXTENSION_JPEG:
                return true;
            default:
                return false;
        }
    }

    /**
     * Saves an image.
     *
     * @param ImageHandler $image the image to save
     * @param string       $file  the path to save the image to
     * @param string       $ext   the file extension or null to take the extension from the file
     *
     * @return bool true on success; false on failure
     */
    private function saveImage(ImageHandler $image, string $file, ?string $ext): bool
    {
        $ext = empty($ext) ? self::getImageInfo($file)->extension : $ext;
        switch (\strtolower($ext)) {
            case self::EXTENSION_BMP:
                return $image->toBmp($file);

            case self::EXTENSION_GIF:
                return $image->toGif($file);

            case self::EXTENSION_JPEG:
            case self::EXTENSION_JPG:
                return $image->toJpeg($file, 100);

            case self::EXTENSION_PNG:
                return $image->toPng($file);

            case self::EXTENSION_XBM:
                return $image->toXbm($file);

            default:
                // format not supported
                return false;
        }
    }
}
