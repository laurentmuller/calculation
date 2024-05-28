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

use App\Tests\KernelServiceTestCase;
use App\Traits\CacheAwareTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Cache\CacheItemPoolInterface;

#[CoversClass(CacheAwareTrait::class)]
class CacheKernelServiceTest extends KernelServiceTestCase
{
    use CacheAwareTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $cacheItemPool = $this->getService(CacheItemPoolInterface::class);
        $this->setCacheItemPool($cacheItemPool);
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
        $actual = $this->getCacheValue('key');
        self::assertNull($actual);

        $key = 'key';
        $value = 'value';
        $this->setCacheValue($key, $value);
        /** @psalm-var string $actual */
        $actual = $this->getCacheValue($key);
        self::assertSame($value, $actual);

        $default = 'new_value';
        $this->deleteCacheItem($key);
        /** @psalm-var string $actual */
        $actual = $this->getCacheValue($key, $default);
        self::assertSame($default, $actual);

        $this->deleteCacheItem($key);
        $callback = fn (): string => $default;
        /** @psalm-var string $actual */
        $actual = $this->getCacheValue($key, $callback);
        self::assertSame($default, $actual);
    }

    public function testHasCacheItem(): void
    {
        $key = 'cache_item';
        $actual = $this->hasCacheItem($key);
        self::assertFalse($actual);

        $this->setCacheValue($key, 'value');
        $actual = $this->hasCacheItem($key);
        self::assertTrue($actual);
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
        $actual = $this->setCacheValue($key, $value);
        self::assertTrue($actual);

        $actual = $this->setCacheValue($key, null);
        self::assertTrue($actual);
    }
}
