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

use Symfony\Contracts\Cache\ItemInterface;

/**
 * Trait to get a clean cache key.
 */
trait CacheKeyTrait
{
    /**
     * Replace all reserved characters that cannot be used in a key by the underscore ('_') character.
     */
    public function cleanKey(string $key): string
    {
        if (false === \strpbrk($key, ItemInterface::RESERVED_CHARACTERS)) {
            return $key;
        }

        /** @phpstan-var string[] $reservedCharacters */
        static $reservedCharacters = [];
        if ([] === $reservedCharacters) {
            $reservedCharacters = \str_split(ItemInterface::RESERVED_CHARACTERS);
        }

        return \str_replace($reservedCharacters, '_', $key);
    }
}
