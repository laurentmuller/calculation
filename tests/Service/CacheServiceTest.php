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
use App\Tests\KernelServiceTestCase;

class CacheServiceTest extends KernelServiceTestCase
{
    private CacheService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
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

    public function testList(): void
    {
        $actual = $this->service->list();
        self::assertNotEmpty($actual);
    }
}
