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

use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;
use Vich\UploaderBundle\Naming\Polyfill\FileExtensionTrait;
use Vich\UploaderBundle\Util\Transliterator;

/**
 * Default file namer.
 *
 * @author Laurent Muller
 */
class EntityFileNamer implements NamerInterface
{
    use FileExtensionTrait;

    private Transliterator $transliterator;

    /**
     * Constructor.
     */
    public function __construct(Transliterator $transliterator)
    {
        $this->transliterator = $transliterator;
    }

    /**
     * {@inheritDoc}
     */
    public function name($object, PropertyMapping $mapping): string
    {
        $file = $mapping->getFile($object);
        $name = $this->transliterate($mapping->getFileNamePropertyName());

        // append the file extension if there is one
        if ($extension = $this->getExtension($file)) {
            $name = \sprintf('%s.%s', $name, $extension);
        }

        return \uniqid() . '_' . $name;
    }

    private function transliterate(string $value): string
    {
        return $this->transliterator->transliterate($value);
    }
}
