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
    private ?CacheItemPoolInterface $cacheAdapter = null;

    /**
     * Clear this cache adapter.
     */
    public function clearCache(): bool
    {
        return $this->getCacheAdapter()->clear();
    }

    /**
     * Persists any deferred cache items.
     */
    public function commitDeferredValues(): bool
    {
        return $this->getCacheAdapter()->commit();
    }

    /**
     * Removes the item from the cache pool.
     */
    public function deleteCacheItem(string $key): bool
    {
        try {
            return $this->getCacheAdapter()->deleteItem(self::cleanKey($key));
        } catch (\Psr\Cache\InvalidArgumentException) {
            return false;
        }
    }

    #[SubscribedService]
    public function getCacheAdapter(): CacheItemPoolInterface
    {
        if (null === $this->cacheAdapter) {
            /* @noinspection PhpUnhandledExceptionInspection */
            /** @psalm-var CacheItemPoolInterface $result */
            $result = $this->container->get(__CLASS__ . '::' . __FUNCTION__);
            $this->cacheAdapter = $result;
        }

        return $this->cacheAdapter;
    }

    /**
     * Gets the cache item for the given key.
     */
    public function getCacheItem(string $key): ?CacheItemInterface
    {
        try {
            return $this->getCacheAdapter()->getItem(self::cleanKey($key));
        } catch (\Psr\Cache\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Gets the value from this cache for the given key.
     *
     * @param string                 $key     The key for which to return the corresponding value
     * @param mixed                  $default The default value to return or a callable function to get the default value.
     *                                        If the callable function returns a not null value, this value is saved to the cache.
     * @param \DateInterval|int|null $time    The period of time from the present after which the item must be considered
     *                                        expired. An integer parameter is understood to be the time in seconds until
     *                                        expiration. If null is passed, the expiration time is not set.
     *
     * @return mixed the value, if found; the default otherwise
     */
    public function getCacheValue(string $key, mixed $default = null, int|\DateInterval|null $time = null): mixed
    {
        $key = self::cleanKey($key);
        $item = $this->getCacheItem($key);
        if (null !== $item && $item->isHit()) {
            return $item->get();
        }
        if (\is_callable($default)) {
            if (null !== $value = \call_user_func($default)) {
                $this->setCacheValue($key, $value, $time);

                return $value;
            }

            return null;
        }

        return $default;
    }

    /**
     * Confirms if the cache contains specified cache item.
     */
    public function hasCacheItem(string $key): bool
    {
        try {
            return $this->getCacheAdapter()->hasItem(self::cleanKey($key));
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
    public function saveDeferredCacheValue(string $key, mixed $value, int|\DateInterval|null $time = null): bool
    {
        if (null !== $item = $this->getCacheItem($key)) {
            $item->set($value);
            if (null !== $time) {
                $item->expiresAfter($time);
            }

            return $this->getCacheAdapter()->saveDeferred($item);
        }

        return false;
    }

    public function setCacheAdapter(CacheItemPoolInterface $cacheAdapter): static
    {
        $this->cacheAdapter = $cacheAdapter;

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
    public function setCacheValue(string $key, mixed $value, int|\DateInterval|null $time = null): bool
    {
        $key = self::cleanKey($key);
        if (null === $value) {
            return $this->deleteCacheItem($key);
        } elseif (null !== $item = $this->getCacheItem($key)) {
            $item->set($value);
            if (null !== $time) {
                $item->expiresAfter($time);
            }

            return $this->getCacheAdapter()->save($item);
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
