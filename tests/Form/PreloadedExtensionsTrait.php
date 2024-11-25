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

use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @psalm-require-extends TypeTestCase
 */
trait PreloadedExtensionsTrait
{
    /**
     * @throws \ReflectionException|Exception
     */
    protected function getExtensions(): array
    {
        /** @psalm-var array $extensions */
        $extensions = parent::getExtensions();
        /** @psalm-var FormTypeInterface[] $preloadedExtensions */
        $preloadedExtensions = $this->getPreloadedExtensions();
        if ([] !== $preloadedExtensions) {
            $extensions[] = new PreloadedExtension($preloadedExtensions, []);
        }

        return $extensions;
    }

    protected function getPreloadedExtensions(): array
    {
        return [];
    }
}
