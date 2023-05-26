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

use App\Entity\User;
use App\Enums\ImageExtension;
use App\Enums\ImageSize;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;

/**
 * Namer for user images.
 *
 * @implements NamerInterface<User>
 */
class UserNamer implements NamerInterface
{
    /**
     * Gets the base file name.
     */
    public static function getBaseName(User|int $key, ImageSize $size, ImageExtension|string $ext = null): string
    {
        $id = (int) ($key instanceof User ? $key->getId() : $key);
        $name = \sprintf('USER_%06d_%03d', $id, $size->value);
        if (null !== $ext && '' !== $ext) {
            if ($ext instanceof ImageExtension) {
                $ext = $ext->value;
            }

            return "$name.$ext";
        }

        return $name;
    }

    public function name($object, PropertyMapping $mapping): string
    {
        return self::getBaseName($object, ImageSize::DEFAULT, ImageExtension::PNG);
    }
}
