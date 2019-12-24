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

namespace App\Listener;

use App\Interfaces\IImageExtension;
use App\Service\ImageResizer;
use App\Service\UserNamer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events;
use Vich\UploaderBundle\Naming\Polyfill\FileExtensionTrait;

/**
 * Listener to resize the profile image.
 *
 * @author Laurent Muller
 */
class VichListener implements EventSubscriberInterface, IImageExtension
{
    use FileExtensionTrait;

    /**
     * @var ImageResizer
     */
    private $resizer;

    /**
     * Constructor.
     */
    public function __construct(ImageResizer $resizer)
    {
        $this->resizer = $resizer;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_UPLOAD => 'onPreUpload',
            Events::PRE_REMOVE => 'onPreRemove',
            Events::POST_UPLOAD => 'onPostUpload',
        ];
    }

    /**
     * Handles post-upload event.
     *
     * Create the small and medium image if applicable.
     *
     * @param Event $event the event
     */
    public function onPostUpload(Event $event): void
    {
        $obj = $event->getObject();
        $mapping = $event->getMapping();

        $file = $mapping->getFile($obj);
        if (!$file || !$file->isReadable()) {
            return;
        }

        // get values
        $source = $file->getRealPath();
        $extension = $file->getExtension();
        $path = $file->getPath().\DIRECTORY_SEPARATOR;

        // create medium image
        $target = $path.UserNamer::getBaseName($obj, self::SIZE_MEDIUM, $extension);
        $this->resize($source, $target, self::SIZE_MEDIUM);

        // create small image
        $target = $path.UserNamer::getBaseName($obj, self::SIZE_SMALL, $extension);
        $this->resize($source, $target, self::SIZE_SMALL);
    }

    /**
     * Handles pre-remove event.
     *
     * Remove the small and medium image if applicable.
     *
     * @param Event $event the event
     */
    public function onPreRemove(Event $event): void
    {
        $obj = $event->getObject();
        $mapping = $event->getMapping();

        // directory
        $path = $mapping->getUploadDestination().\DIRECTORY_SEPARATOR;

        // get file extension
        $fileName = $mapping->getFileName($obj);
        $file = new File($fileName, false);
        $ext = $file->getExtension();

        // delete medium image
        $fileName = $path.UserNamer::getBaseName($obj, self::SIZE_MEDIUM, $ext);
        if (\file_exists($fileName)) {
            \unlink($fileName);
        }

        // delete small image
        $fileName = $path.UserNamer::getBaseName($obj, self::SIZE_SMALL, $ext);
        if (\file_exists($fileName)) {
            \unlink($fileName);
        }
    }

    /**
     * Handles pre-upload event.
     *
     * Resize the image if applicable.
     *
     * @param Event $event the event
     */
    public function onPreUpload(Event $event): void
    {
        $obj = $event->getObject();
        $mapping = $event->getMapping();

        // downloaded file
        $file = $mapping->getFile($obj);
        if (!$file || !$file->isReadable()) {
            return;
        }

        // target file name
        if (!$name = $mapping->getUploadName($obj)) {
            return;
        }

        // source
        $source = $file->getRealPath();
        $sourceExt = $this->getExtension($file);
        $sourceExt = empty($sourceExt) ? self::EXTENSION_PNG : \strtolower($sourceExt);

        // resize
        if ($this->resize($source, $source, self::SIZE_DEFAULT, $sourceExt, self::EXTENSION_PNG)) {
            // rename if not same extension
            if (self::EXTENSION_PNG !== $sourceExt) {
                $newName = \substr_replace($name, self::EXTENSION_PNG, \strrpos($name, '.') + 1);
                $mapping->setFileName($obj, $newName);
            }
        }
    }

    /**
     * Resize the given image.
     *
     * @param string $source    the source file
     * @param string $target    the target file
     * @param int    $size      the image size
     * @param string $sourceExt the source file extension or null to use the source file extension
     * @param string $targetExt the target file extension or null to use the source extension
     *
     * @return bool true if the image is resized is successull; false if fail
     */
    private function resize(string $source, string $target, int $size = self::SIZE_DEFAULT, ?string $sourceExt = null, ?string $targetExt = null): bool
    {
        // get image size
        list($width, $height) = \getimagesize($source);
        if ($height > $width) {
            $height = $size;
            $width = 0;
        } elseif ($height < $width) {
            $height = 0;
            $width = $size;
        } else {
            $height = $width = $size;
        }

        return $this->resizer->resize($source, $target, $height, $width, $sourceExt, $targetExt, false);
    }
}
