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

use App\Entity\User;
use App\Interfaces\IImageExtension;
use Vich\UploaderBundle\Exception\NameGenerationException;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;

/**
 * Namer for user images.
 *
 * @author Laurent Muller
 */
class UserNamer implements NamerInterface, IImageExtension
{
    /**
     * Gets the base file name.
     *
     * @param User        $user the user
     * @param int         $size the image size to use
     * @param string|null $ext  the optional file extension
     *
     * @return string the file name
     */
    public static function getBaseName(User $user, int $size, ?string $ext = null): string
    {
        $name = \sprintf('USER_%06d_%03d', (int) $user->getId(), $size);
        if ($ext) {
            return "{$name}.{$ext}";
        }

        return $name;
    }

    /**
     * {@inheritdoc}
     */
    public function name($object, PropertyMapping $mapping): string
    {
        if (!$object instanceof User) {
            throw new NameGenerationException('The name could not be generated. The object must be an instance of User.');
        }

        // use FileExtensionTrait;
        // $file = $mapping->getFile($object);
        // $ext = $this->getExtension($file);

        return self::getBaseName($object, self::SIZE_DEFAULT, self::EXTENSION_PNG);
    }
}
