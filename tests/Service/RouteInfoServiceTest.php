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

use App\Service\RouteInfoService;
use App\Tests\KernelServiceTestCase;

final class RouteInfoServiceTest extends KernelServiceTestCase
{
    private RouteInfoService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(RouteInfoService::class);
    }

    public function testGetDebugRoutes(): void
    {
        $actual = $this->service->getDebugRoutes();
        self::assertEmpty($actual);
    }

    public function testGetRuntimeRoutes(): void
    {
        $actual = $this->service->getRuntimeRoutes();
        self::assertNotEmpty($actual);
    }
}
