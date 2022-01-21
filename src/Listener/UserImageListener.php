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

use App\Entity\User;
use App\Interfaces\ImageExtensionInterface;
use App\Service\UserNamer;
use App\Util\FileUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;

/**
 * Listener to update the profile image.
 *
 * @author Laurent Muller
 */
class UserImageListener implements ImageExtensionInterface
{
    private PropertyMappingFactory $factory;

    /**
     * Constructor.
     */
    public function __construct(PropertyMappingFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Handle the entity post persist.
     */
    public function postPersist(LifecycleEventArgs $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof User) {
            return;
        }
        if (null === $imageName = $entity->getImageName()) {
            return;
        }
        if (!\preg_match('/0{6}/m', $imageName)) {
            return;
        }
        if (null === $mapping = $this->factory->fromField($entity, 'imageFile')) {
            return;
        }

        $dir = $mapping->getUploadDestination() . \DIRECTORY_SEPARATOR;
        $ext = \pathinfo($imageName, \PATHINFO_EXTENSION);

        $this->rename($dir, $entity->getId(), self::SIZE_MEDIUM, $ext);
        $this->rename($dir, $entity->getId(), self::SIZE_SMALL, $ext);
        $newName = $this->rename($dir, $entity->getId(), self::SIZE_DEFAULT, $ext);

        // update user
        $entity->setImageName($newName);
        $manager = $event->getEntityManager();
        $manager->persist($entity);
        $manager->flush();
    }

    private function rename(string $dir, int $id, int $size, string $ext): string
    {
        $oldName = UserNamer::getBaseName(0, $size, $ext);
        $newName = UserNamer::getBaseName($id, $size, $ext);
        FileUtils::rename($dir . $oldName, $dir . $newName);

        return $newName;
    }
}
