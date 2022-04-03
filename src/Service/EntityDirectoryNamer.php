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

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;
use Vich\UploaderBundle\Util\Transliterator;

/**
 * Default directory namer. The directory name is created with the simple class name.
 *
 * @author Laurent Muller
 */
class EntityDirectoryNamer implements DirectoryNamerInterface
{
    /**
     * Constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly Transliterator $transliterator
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function directoryName($object, PropertyMapping $mapping): string
    {
        $name = $this->getShortClassName($object);
        $identifier = $this->getIdentifier($object);

        return $name . \DIRECTORY_SEPARATOR . $identifier;
    }

    private function getIdentifier(object $object): string
    {
        $className = $object::class;
        $identifiers = $this->manager->getClassMetadata($className)
            ->getIdentifierValues($object);
        $identifier = (string) \reset($identifiers);

        return $this->transliterate($identifier);
    }

    private function getShortClassName(object $object): string
    {
        $className = $object::class;
        $classParts = \explode('\\', $className);
        $firstPart = \array_pop($classParts);

        return $this->transliterate($firstPart);
    }

    /**
     * @psalm-suppress InternalMethod
     */
    private function transliterate(string $value): string
    {
        return $this->transliterator->transliterate($value);
    }
}
