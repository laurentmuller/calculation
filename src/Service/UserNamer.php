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
    /**
     * @phpstan-param User $object
     */
    #[\Override]
    public function name(object $object, PropertyMapping $mapping): string
    {
        $id = (int) $object->getId();
        $name = \sprintf('USER_%06d_%03d', $id, ImageResizer::IMAGE_SIZE);

        return \sprintf('%s.%s', $name, ImageExtension::PNG->value);
    }
}
