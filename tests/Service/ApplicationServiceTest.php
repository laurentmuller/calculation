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

use App\Service\ApplicationService;
use App\Tests\KernelServiceTestCase;

final class ApplicationServiceTest extends KernelServiceTestCase
{
    public function testService(): void
    {
        $service = $this->getService(ApplicationService::class);
        self::assertNotEmpty($service->getDescription());
        self::assertNotEmpty($service->getFullName());
        self::assertSame('calculation@bibi.nu', $service->getMailerEmail());
        self::assertSame('Calculation', $service->getMailerName());
        self::assertSame('Calculation', $service->getName());
        self::assertSame('Montévraz', $service->getOwnerCity());
        self::assertSame('bibi.nu', $service->getOwnerName());
        self::assertSame('https://www.bibi.nu', $service->getOwnerUrl());
        self::assertNotEmpty($service->getVersion());

        $actual = $service->getMailerAddress();
        self::assertSame('calculation@bibi.nu', $actual->getAddress());
        self::assertSame('Calculation', $actual->getName());
    }
}
