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

namespace App\Tests\Form;

use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @phpstan-require-extends TypeTestCase
 */
trait PreloadedExtensionsTrait
{
    /**
     * @return FormExtensionInterface[]
     *
     * @throws \ReflectionException
     */
    protected function getExtensions(): array
    {
        $extensions = parent::getExtensions();
        $preloadedExtensions = $this->getPreloadedExtensions();
        if ([] !== $preloadedExtensions) {
            $extensions[] = new PreloadedExtension($preloadedExtensions, []);
        }

        return $extensions;
    }

    /**
     * @phpstan-return FormTypeInterface<mixed>[]
     */
    abstract protected function getPreloadedExtensions(): array;
}
