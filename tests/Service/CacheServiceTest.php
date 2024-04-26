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

namespace App\Tests\Service;

use App\Service\CacheService;
use App\Tests\ServiceTrait;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(CacheService::class)]
class CacheServiceTest extends KernelTestCase
{
    use ServiceTrait;

    private CacheService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = $this->getService(CacheService::class);
    }

    /**
     * @throws \Exception
     */
    public function testClear(): void
    {
        $actual = $this->service->clear();
        self::assertTrue($actual);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testList(): void
    {
        $actual = $this->service->list();
        self::assertNotEmpty($actual);
    }
}
