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

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Trait to manage cache values.
 *
 * @property CacheItemPoolInterface $cacheItemPool
 */
trait CacheTrait
{
    use CacheKeyTrait;

    /**
     * Clear this cache adapter.
     */
    public function clearCache(): bool
    {
        return $this->cacheItemPool->clear();
    }

    /**
     * Persists any deferred cache items.
     */
    public function commitDeferredValues(): bool
    {
        return $this->cacheItemPool->commit();
    }

    /**
     * Removes the item from the cache pool.
     *
     * @throws \LogicException if an exception occurs
     */
    public function deleteCacheItem(string $key): bool
    {
        return $this->cacheItemPool->deleteItem($this->cleanKey($key));
    }

    /**
     * Gets the cache item for the given key.
     *
     * @throws \LogicException if an exception occurs
     */
    public function getCacheItem(string $key): CacheItemInterface
    {
        return $this->cacheItemPool->getItem($this->cleanKey($key));
    }

    /**
     * Gets the default cache timeout.
     *
     * @return \DateInterval|int|null The period from the present after which the item must be considered
     *                                expired. An integer parameter is understood to be the time in seconds until
     *                                expiration. If null is returned, the expiration time is not set.
     */
    public function getCacheTimeout(): \DateInterval|int|null
    {
        return null;
    }

    /**
     * Gets the value from this cache for the given key.
     *
     * @param string $key     The key for which to return the corresponding value
     * @param mixed  $default the default value to return if not found in the cache
     *
     * @return mixed the value, if found in the cache; the default value otherwise
     */
    public function getCacheValue(string $key, mixed $default = null): mixed
    {
        $item = $this->getCacheItem($key);

        return $item->isHit() ? $item->get() : $default;
    }

    /**
     * Sets a cache item value to be persisted later.
     *
     * @param string                 $key   The key for which to save the value
     * @param mixed                  $value The value to save. If null, the key item is removed from the cache.
     * @param \DateInterval|int|null $time  The period from the present after which the item must be considered
     *                                      expired. An integer parameter is understood to be the time in seconds until
     *                                      expiration. If null is passed, the expiration time is not set.
     */
    public function saveDeferredCacheValue(string $key, mixed $value, int|\DateInterval|null $time = null): bool
    {
        $item = $this->getCacheItem($key)
            ->expiresAfter($time ?? $this->getCacheTimeout())
            ->set($value);

        return $this->cacheItemPool->saveDeferred($item);
    }

    /**
     * Save the given value to the cache.
     *
     * If the value is null, the item is removed from the cache.
     *
     * @param string                 $key   The key for which to save the value
     * @param mixed                  $value The value to save. If null, the item is removed from the cache.
     * @param \DateInterval|int|null $time  The period from the present after which the item must be considered
     *                                      expired. An integer parameter is understood to be the time in seconds until
     *                                      expiration. If null is passed, the expiration time is not set.
     *
     * @return bool true if the cache is updated
     */
    public function setCacheValue(string $key, mixed $value, int|\DateInterval|null $time = null): bool
    {
        if (null === $value) {
            return $this->deleteCacheItem($key);
        }

        $item = $this->getCacheItem($key)
            ->expiresAfter($time ?? $this->getCacheTimeout())
            ->set($value);

        return $this->cacheItemPool->save($item);
    }
}
