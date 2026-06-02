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
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;

/**
 * Namer for user images.
 *
 * @implements NamerInterface<User>
 */
class UserNamer implements NamerInterface
{
    #[\Override]
    public function name(object|array $object, PropertyMapping $mapping): string
    {
        if (!$object instanceof User) {
            throw new \InvalidArgumentException(\sprintf('Expected argument of type "%s", "%s" given.', User::class, \get_debug_type($object)));
        }
        $id = $object->getId() ?? 0;
        $name = \sprintf('USER_%06d_%03d', $id, ImageResizer::IMAGE_SIZE);

        return ImageExtension::PNG->changeExtension($name);
    }
}
