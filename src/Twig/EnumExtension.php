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

namespace App\Twig;

use App\Interfaces\EnumSortableInterface;
use Twig\Attribute\AsTwigFunction;
use Twig\Error\RuntimeError;

/**
 * Twig extension for sorted enums.
 */
class EnumExtension
{
    /**
     * Gets sorted enumerations.
     *
     * @template T of \UnitEnum
     *
     * @param class-string<T> $enum the enumeration class name
     *
     * @return T[]
     *
     * @throws RuntimeError if the given class name is not an enum or is not a sortable enum
     */
    #[AsTwigFunction(name: 'enum_sorted')]
    public static function sorted(string $enum): array
    {
        if (!\enum_exists($enum)) {
            throw new RuntimeError(\sprintf('"%s" is not an enum.', $enum));
        }
        if (!\is_a($enum, EnumSortableInterface::class, true)) {
            throw new RuntimeError(\sprintf('"%s" is not a sortable enum.', $enum));
        }

        /** @phpstan-var T[] */
        return $enum::sorted();
    }
}
