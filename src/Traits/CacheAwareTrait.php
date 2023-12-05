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
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Service\Attribute\SubscribedService;

/**
 * Trait to save or load data from a cache.
 *
 * @property \Psr\Container\ContainerInterface $container
 */
trait CacheAwareTrait
{
    /**
     * The cache.
     */
    private ?CacheItemPoolInterface $cacheItemPool = null;

    /**
     * Clear this cache adapter.
     */
    public function clearCache(): bool
    {
        return $this->getCacheItemPool()->clear();
    }

    /**
     * Persists any deferred cache items.
     */
    public function commitDeferredValues(): bool
    {
        return $this->getCacheItemPool()->commit();
    }

    /**
     * Removes the item from the cache pool.
     */
    public function deleteCacheItem(string $key): bool
    {
        try {
            return $this->getCacheItemPool()->deleteItem(self::cleanKey($key));
        } catch (\Psr\Cache\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Gets the cache item for the given key.
     */
    public function getCacheItem(string $key): ?CacheItemInterface
    {
        try {
            return $this->getCacheItemPool()->getItem(self::cleanKey($key));
        } catch (\Psr\Cache\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Get the cache item pool.
     */
    #[SubscribedService]
    public function getCacheItemPool(): CacheItemPoolInterface
    {
        if (null === $this->cacheItemPool) {
            /* @noinspection PhpUnhandledExceptionInspection */
            $this->cacheItemPool = $this->container->get(self::class . '::' . __FUNCTION__);
        }

        return $this->cacheItemPool;
    }

    /**
     * Gets the default cache timeout.
     *
     * @return \DateInterval|int|null The period of time from the present after which the item must be considered
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
     * @param string                 $key     The key for which to return the corresponding value
     * @param mixed                  $default The default value to return or a callable function to get the default
     *                                        value. If the callable function returns a not null value, this value is
     *                                        saved to the cache.
     * @param \DateInterval|int|null $time    The period of time from the present after which the item must be
     *                                        considered expired. An integer parameter is understood to be the time in
     *                                        seconds until expiration. If null is passed, the expiration time is not
     *                                        set.
     *
     * @return mixed the value, if found; the default otherwise
     */
    public function getCacheValue(string $key, mixed $default = null, int|\DateInterval $time = null): mixed
    {
        $key = self::cleanKey($key);
        $item = $this->getCacheItem($key);
        if (null !== $item && $item->isHit()) {
            return $item->get();
        }
        if (!\is_callable($default)) {
            return $default;
        }
        $value = \call_user_func($default);
        if (null !== $value) {
            $this->setCacheValue($key, $value, $time);
        }

        return $value;
    }

    /**
     * Confirms if the cache contains specified cache item.
     */
    public function hasCacheItem(string $key): bool
    {
        try {
            return $this->getCacheItemPool()->hasItem(self::cleanKey($key));
        } catch (\Psr\Cache\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Sets a cache item value to be persisted later.
     *
     * @param string                 $key   The key for which to save the value
     * @param mixed                  $value The value to save. If null, the key item is removed from the cache.
     * @param \DateInterval|int|null $time  The period of time from the present after which the item must be considered
     *                                      expired. An integer parameter is understood to be the time in seconds until
     *                                      expiration. If null is passed, the expiration time is not set.
     */
    public function saveDeferredCacheValue(string $key, mixed $value, int|\DateInterval $time = null): bool
    {
        $item = $this->getCacheItem($key);
        if (null !== $item) {
            $item->set($value);
            if (null !== $time) {
                $item->expiresAfter($time);
            }

            return $this->getCacheItemPool()->saveDeferred($item);
        }

        return false;
    }

    public function setCacheItemPool(CacheItemPoolInterface $cacheItemPool): static
    {
        $this->cacheItemPool = $cacheItemPool;

        return $this;
    }

    /**
     * Save the given value to the cache.
     *
     * If the value is null, the item is removed from the cache.
     *
     * @param string                 $key   The key for which to save the value
     * @param mixed                  $value The value to save. If null, the key item is removed from the cache.
     * @param \DateInterval|int|null $time  The period of time from the present after which the item must be considered
     *                                      expired. An integer parameter is understood to be the time in seconds until
     *                                      expiration. If null is passed, the expiration time is not set.
     *
     * @return bool true if the cache is updated
     */
    public function setCacheValue(string $key, mixed $value, int|\DateInterval $time = null): bool
    {
        $key = self::cleanKey($key);
        if (null === $value) {
            return $this->deleteCacheItem($key);
        }
        $item = $this->getCacheItem($key);
        if (null !== $item) {
            $item->expiresAfter($time ?? $this->getCacheTimeout())
                ->set($value);

            return $this->getCacheItemPool()->save($item);
        }

        return false;
    }

    /**
     * Replace all reserved characters that cannot be used in a key by the underscore ('_') character.
     */
    private static function cleanKey(string $key): string
    {
        /** @psalm-var string[] $reservedCharacters */
        static $reservedCharacters = [];
        if ([] === $reservedCharacters) {
            $reservedCharacters = \str_split(ItemInterface::RESERVED_CHARACTERS);
        }

        return \str_replace($reservedCharacters, '_', $key);
    }
}
