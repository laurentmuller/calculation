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
 */
trait CacheAwareTrait
{
    /**
     * The cache.
     */
    private ?CacheItemPoolInterface $cacheAdapter = null;

    /**
     * The reserved characters.
     *
     * @var string[]|null
     */
    private static ?array $reservedCharacters = null;

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
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function deleteCacheItem(string $key): bool
    {
        return $this->getCacheAdapter()->deleteItem(self::cleanKey($key));
    }

    #[SubscribedService]
    public function getCacheAdapter(): CacheItemPoolInterface
    {
        if (null === $this->cacheAdapter) {
            /** @psalm-var CacheItemPoolInterface $result */
            $result = $this->container->get(__CLASS__ . '::' . __FUNCTION__);
            $this->cacheAdapter = $result;
        }

        return $this->cacheAdapter;
    }

    /**
     * Gets the cache item for the given key.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getCacheItem(string $key): ?CacheItemInterface
    {
        return $this->getCacheAdapter()->getItem(self::cleanKey($key));
    }

    /**
     * Gets the value from this cache for the given key.
     *
     * @param string $key     The key for which to return the corresponding value
     * @param mixed  $default The default value to return or a callable function to get the default value.
     *                        If the callable function returns a value, this value is saved to the cache.
     *
     * @return mixed the value, if found; the default otherwise
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getCacheValue(string $key, mixed $default = null): mixed
    {
        $key = self::cleanKey($key);
        if (($item = $this->getCacheItem($key)) && $item->isHit()) {
            return $item->get();
        }

        if (\is_callable($default)) {
            $value = \call_user_func($default);
            if (null !== $value) {
                $this->setCacheValue($key, $value);

                return $value;
            }

            return null;
        }

        return $default;
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function hasCacheItem(string $key): bool
    {
        return $this->getCacheAdapter()->hasItem(self::cleanKey($key));
    }

    /**
     * Sets a cache item value to be persisted later.
     *
     * @param string                 $key   The key for which to save the value
     * @param mixed                  $value The value to save. If null, the key item is removed from the cache.
     * @param \DateInterval|int|null $time  The period of time from the present after which the item must be considered
     *                                      expired. An integer parameter is understood to be the time in seconds until
     *                                      expiration. If null is passed, the expiration time is not set.
     *
     * @throws \Psr\Cache\InvalidArgumentException
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
     * Save the given value to the cache. If the value is null, the item is removed from the cache.
     *
     * @param string                 $key   The key for which to save the value
     * @param mixed                  $value The value to save. If null, the key item is removed from the cache.
     * @param \DateInterval|int|null $time  The period of time from the present after which the item must be considered
     *                                      expired. An integer parameter is understood to be the time in seconds until
     *                                      expiration. If null is passed, the expiration time is not set.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function setCacheValue(string $key, mixed $value, int|\DateInterval|null $time = null): static
    {
        $key = self::cleanKey($key);
        if (null === $value) {
            $this->deleteCacheItem($key);
        } elseif (null !== $item = $this->getCacheItem($key)) {
            $item->set($value);
            if (null !== $time) {
                $item->expiresAfter($time);
            }
            $this->getCacheAdapter()->save($item);
        }

        return $this;
    }

    /**
     * Remove all reserved characters that cannot be used in a key.
     */
    private static function cleanKey(string $key): string
    {
        if (null === self::$reservedCharacters) {
            self::$reservedCharacters = \str_split(ItemInterface::RESERVED_CHARACTERS);
        }

        return \str_replace(self::$reservedCharacters, '_', $key);
    }
}
