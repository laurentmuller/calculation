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

namespace App\Listener;

use App\Entity\User;
use App\Enums\ImageExtension;
use App\Enums\ImageSize;
use App\Service\ImageResizer;
use App\Service\UserNamer;
use App\Utils\FileUtils;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\Polyfill\FileExtensionTrait;

/**
 * Listener to resize the profile image.
 */
#[AsEventListener(event: Events::PRE_UPLOAD, method: 'onPreUpload')]
#[AsEventListener(event: Events::PRE_REMOVE, method: 'onPreRemove')]
#[AsEventListener(event: Events::POST_UPLOAD, method: 'onPostUpload')]
class VichListener
{
    use FileExtensionTrait;

    /**
     * Constructor.
     */
    public function __construct(private readonly ImageResizer $resizer)
    {
    }

    /**
     * Create the small and medium image if applicable.
     */
    public function onPostUpload(Event $event): void
    {
        /** @var User $user */
        $user = $event->getObject();
        $mapping = $event->getMapping();
        $file = $mapping->getFile($user);
        if (!$file instanceof File || !$file->isReadable()) {
            return;
        }

        // new?
        if (\preg_match('/0{6}/m', $file->getFilename())) {
            $file = $this->rename($mapping, $user, $file);
        }

        $source = FileUtils::realPath($file);
        $this->resizer->resizeMedium($source, $this->buildPath($user, ImageSize::MEDIUM, $file));
        $this->resizer->resizeSmall($source, $this->buildPath($user, ImageSize::SMALL, $file));
    }

    /**
     * Remove the small and medium image if applicable.
     */
    public function onPreRemove(Event $event): void
    {
        /** @var User $user */
        $user = $event->getObject();
        $mapping = $event->getMapping();
        $path = $mapping->getUploadDestination();
        $name = (string) $mapping->getFileName($user);
        $file = new File(FileUtils::buildPath($path, $name), false);

        // delete medium images
        FileUtils::remove($this->buildPath($user, ImageSize::MEDIUM, $file));
        FileUtils::remove($this->buildPath($user, ImageSize::SMALL, $file));
    }

    /**
     * Rename and resize the image if applicable.
     */
    public function onPreUpload(Event $event): void
    {
        /** @var User $user */
        $user = $event->getObject();
        $mapping = $event->getMapping();
        $file = $mapping->getFile($user);
        if (!$file instanceof UploadedFile || !$file->isReadable()) {
            return;
        }

        // target file name
        $name = $mapping->getUploadName($user);
        if ('' === $name) {
            return;
        }

        // resize
        $source = FileUtils::realPath($file);
        $this->resizer->resizeDefault($source, $source);

        // rename if not PNG
        $png = ImageExtension::PNG->value;
        if ($png !== $this->getFileExtension($file)) {
            $newName = Path::changeExtension($name, $png);
            $mapping->setFileName($user, $newName);
        }
    }

    private function buildPath(user $user, ImageSize $size, File $file): string
    {
        $path = $file->getPath();
        $ext = $file->getExtension();
        $baseName = UserNamer::getBaseName($user, $size, $ext);

        return FileUtils::buildPath($path, $baseName);
    }

    /**
     * Gets the file extension.
     */
    private function getFileExtension(UploadedFile $file): string
    {
        $extension = $this->getExtension($file);

        return empty($extension) ? ImageExtension::PNG->value : \strtolower($extension);
    }

    private function rename(PropertyMapping $mapping, User $user, File $file): File
    {
        $name = UserNamer::getBaseName($user, ImageSize::DEFAULT, $file->getExtension());
        $path = FileUtils::buildPath($file->getPath(), $name);
        $newFile = new File($path, false);

        FileUtils::rename($file->getPathname(), $newFile->getPathname());

        $mapping->setFileName($user, $name);
        $mapping->setFile($user, $newFile);

        return $newFile;
    }
}
