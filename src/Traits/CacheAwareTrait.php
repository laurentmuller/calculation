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
     * The cache adapter.
     */
    private ?CacheItemPoolInterface $adapter = null;
    /**
     * The debug state.
     */
    private bool $isDebugCache = false;

    /**
     * The reserved characters.
     *
     * @var string[]|null
     */
    private static ?array $reservedCharacters = null;

    /**
     * Remove all reserved characters that cannot be used in a key.
     */
    public function cleanKey(string $key): string
    {
        if (null === self::$reservedCharacters) {
            self::$reservedCharacters = \str_split(ItemInterface::RESERVED_CHARACTERS);
        }

        return \str_replace(self::$reservedCharacters, '_', $key);
    }

    /**
     * Clear this cache adapter.
     */
    public function clearCache(): bool
    {
        if (!$this->isDebugCache) {
            return $this->adapter()->clear();
        }

        return false;
    }

    /**
     * Persists any deferred cache items.
     */
    public function commitDeferredValues(): bool
    {
        if (!$this->isDebugCache) {
            return $this->adapter()->commit();
        }

        return false;
    }

    /**
     * Removes the item from the cache pool.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function deleteCacheItem(string $key): bool
    {
        if (!$this->isDebugCache) {
            return $this->adapter()->deleteItem($this->cleanKey($key));
        }

        return false;
    }

    /**
     * Removes multiple items from the cache pool.
     *
     * @param string[] $keys An array of keys that should be removed from the pool
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function deleteCacheItems(array $keys): bool
    {
        $keys = \array_map(fn (string $key): string => $this->cleanKey($key), $keys);
        if (!$this->isDebugCache) {
            return $this->adapter()->deleteItems($keys);
        }

        return false;
    }

    /**
     * Gets the cache item for the given key.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getCacheItem(string $key): ?CacheItemInterface
    {
        return $this->isDebugCache ? null : $this->adapter()->getItem($this->cleanKey($key));
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
        $key = $this->cleanKey($key);
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
        $item = $this->getCacheItem($key);
        if (!$this->isDebugCache && null !== $item) {
            $item->set($value);
            if (null !== $time) {
                $item->expiresAfter($time);
            }

            return $this->adapter()->saveDeferred($item);
        }

        return false;
    }

    /**
     * Save the given value to the cache.
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
        $key = $this->cleanKey($key);
        if (null === $value) {
            $this->deleteCacheItem($key);
        } elseif (!$this->isDebugCache && null !== $item = $this->getCacheItem($key)) {
            $item->set($value);
            if (null !== $time) {
                $item->expiresAfter($time);
            }
            $this->adapter()->save($item);
        }

        return $this;
    }

    #[SubscribedService]
    private function adapter(): CacheItemPoolInterface
    {
        if (null === $this->adapter) {
            /** @psalm-var CacheItemPoolInterface $result */
            $result = $this->container->get(__CLASS__ . '::' . __FUNCTION__);
            $this->adapter = $result;
        }

        return $this->adapter;
    }

    private function setAdapter(CacheItemPoolInterface $adapter): static
    {
        $this->adapter = $adapter;

        return $this;
    }
}
