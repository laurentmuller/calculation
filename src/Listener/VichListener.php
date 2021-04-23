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

namespace App\Listener;

use App\Interfaces\ImageExtensionInterface;
use App\Service\ImageResizer;
use App\Service\UserNamer;
use App\Util\FileUtils;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events;
use Vich\UploaderBundle\Naming\Polyfill\FileExtensionTrait;

/**
 * Listener to resize the profile image.
 *
 * @author Laurent Muller
 */
class VichListener implements EventSubscriberInterface, ImageExtensionInterface
{
    use FileExtensionTrait;

    private ImageResizer $resizer;

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
        $path = $file->getPath() . \DIRECTORY_SEPARATOR;

        // create medium image
        $target = $path . UserNamer::getBaseName($obj, self::SIZE_MEDIUM, $extension);
        $this->resizer->resizeMedium($source, $target);

        // create small image
        $target = $path . UserNamer::getBaseName($obj, self::SIZE_SMALL, $extension);
        $this->resizer->resizeSmall($source, $target);
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
        $path = $mapping->getUploadDestination() . \DIRECTORY_SEPARATOR;

        // get file extension
        $filename = $mapping->getFileName($obj);
        $file = new File($filename, false);
        $ext = $file->getExtension();

        // delete medium image
        $filename = $path . UserNamer::getBaseName($obj, self::SIZE_MEDIUM, $ext);
        FileUtils::remove($filename);

        // delete small image
        $filename = $path . UserNamer::getBaseName($obj, self::SIZE_SMALL, $ext);
        FileUtils::remove($filename);
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

        /** @var ?UploadedFile $file */
        $file = $mapping->getFile($obj);
        if (!$file || !$file->isReadable()) {
            return;
        }

        // target file name
        if (!$name = $mapping->getUploadName($obj)) {
            return;
        }

        // resize
        $source = $file->getRealPath();
        $this->resizer->resizeDefault($source, $source);

        // rename if not same extension
        $extension = $this->getFileExtension($file);
        if (self::EXTENSION_PNG !== $extension) {
            $newName = \substr_replace($name, self::EXTENSION_PNG, \strrpos($name, '.') + 1);
            $mapping->setFileName($obj, $newName);
        }
    }

    /**
     * Gets the file extension.
     */
    private function getFileExtension(UploadedFile $file): string
    {
        $extension = $this->getExtension($file);

        return empty($extension) ? self::EXTENSION_PNG : \strtolower($extension);
    }
}
