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
use App\Service\ImageResizer;
use App\Utils\FileUtils;
use App\Utils\StringUtils;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events;
use Vich\UploaderBundle\Naming\Polyfill\FileExtensionTrait;

/**
 * Listener to resize the user profile image.
 */
class VichListener
{
    use FileExtensionTrait;

    public function __construct(private readonly ImageResizer $resizer)
    {
    }

    /**
     * Rename and resize the image.
     */
    #[AsEventListener(event: Events::PRE_UPLOAD)]
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
        $this->resizer->resize($file->getRealPath());

        // rename extension if not PNG
        if (ImageExtension::PNG->value !== $this->getFileExtension($file)) {
            $newName = FileUtils::changeExtension($name, ImageExtension::PNG);
            $mapping->setFileName($user, $newName);
        }
    }

    /**
     * Gets the file extension.
     */
    private function getFileExtension(UploadedFile $file): string
    {
        $extension = $this->getExtension($file);

        return StringUtils::isString($extension) ? \strtolower($extension) : ImageExtension::PNG->value;
    }
}
