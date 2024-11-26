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

namespace App\Tests\Traits;

use App\Traits\CacheTrait;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class CacheTraitTest extends TestCase
{
    use CacheTrait;

    private CacheItemPoolInterface $cacheItemPool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheItemPool = new ArrayAdapter();
    }

    public function testClearCache(): void
    {
        $actual = $this->clearCache();
        self::assertTrue($actual);
    }

    public function testCommitDeferredValues(): void
    {
        $actual = $this->commitDeferredValues();
        self::assertTrue($actual);
    }

    public function testDeleteCacheItem(): void
    {
        $actual = $this->deleteCacheItem('key');
        self::assertTrue($actual);
    }

    public function testGetCacheItem(): void
    {
        $key = 'key';
        $this->deleteCacheItem($key);
        $actual = $this->getCacheItem($key);
        self::assertFalse($actual->isHit());
    }

    public function testGetCacheTimeout(): void
    {
        $actual = $this->getCacheTimeout();
        self::assertNull($actual);
    }

    public function testGetCacheValue(): void
    {
        /** @psalm-var string|null $actual */
        $actual = $this->getCacheValue('key');
        self::assertNull($actual);

        $key = 'key';
        $value = 'value';
        $this->setCacheValue($key, $value);
        /** @psalm-var string|null $actual */
        $actual = $this->getCacheValue($key);
        self::assertSame($value, $actual);

        $default = 'new_value';
        $this->deleteCacheItem($key);
        /** @psalm-var string $actual */
        $actual = $this->getCacheValue($key, $default);
        self::assertSame($default, $actual);
    }

    public function testSaveDeferredCacheValue(): void
    {
        $key = 'deferred';
        $value = 'value';
        $actual = $this->saveDeferredCacheValue($key, $value, 1000);
        self::assertTrue($actual);
    }

    public function testSetCacheValue(): void
    {
        $key = 'cache_key';
        $value = 'cache_value';
        $actual = $this->setCacheValue($key, $value, 1000);
        self::assertTrue($actual);

        $actual = $this->setCacheValue($key, null);
        self::assertTrue($actual);
    }
}
