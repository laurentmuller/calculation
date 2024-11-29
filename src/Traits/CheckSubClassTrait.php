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

namespace App\Traits;

/**
 * Trait to check subclass parameter.
 */
trait CheckSubClassTrait
{
    /**
     * Check if the given source is a class or a subclass of the given target class name.
     *
     * @throws \InvalidArgumentException if checking failed
     *
     * @psalm-param class-string $target
     */
    public function checkSubClass(string|object $source, string $target): void
    {
        if (!\is_a($source, $target, true) && !\is_subclass_of($source, $target)) {
            $type = \is_string($source) ? $source : \get_debug_type($source);
            throw new \InvalidArgumentException(\sprintf('Expected argument of type "%s", "%s" given.', $target, $type));
        }
    }
}
