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

use App\Service\BundleInfoService;
use App\Tests\KernelServiceTestCase;

final class BundleInfoServiceTest extends KernelServiceTestCase
{
    private BundleInfoService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(BundleInfoService::class);
    }

    public function testGetBundles(): void
    {
        $actual = $this->service->getBundles();
        self::assertNotEmpty($actual);
    }
}
