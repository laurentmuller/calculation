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

use App\Service\EntityNameService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class EntityNameServiceTest extends TestCase
{
    public function testWitDebugAndWithoutGranted(): void
    {
        $service = $this->createService(true, false);
        $actual = $service->getEntities();
        self::assertCount(8, $actual);
    }

    public function testWithDebugAndWithGranted(): void
    {
        $service = $this->createService(true, true);
        $actual = $service->getEntities();
        self::assertCount(10, $actual);
    }

    public function testWithoutDebugAndWithGranted(): void
    {
        $service = $this->createService(false, true);
        $actual = $service->getEntities();
        self::assertCount(9, $actual);
    }

    public function testWithoutDebugAndWithoutGranted(): void
    {
        $service = $this->createService(false, false);
        $actual = $service->getEntities();
        self::assertCount(7, $actual);
    }

    private function createService(bool $debug, bool $granted): EntityNameService
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')
            ->willReturn($granted);

        return new EntityNameService($debug, $security);
    }
}
