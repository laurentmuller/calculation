<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Tests\Service;

use App\Interfaces\ApplicationServiceInterface;
use App\Service\ApplicationService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Test for the application service.
 *
 * @author Laurent Muller
 */
class ApplicationServiceTest extends KernelTestCase implements ApplicationServiceInterface
{
    public function testService(): void
    {
        self::bootKernel();

        /** @var ApplicationService $service */
        $service = self::$container->get(ApplicationService::class);

        $service->setProperties([
            self::CUSTOMER_NAME => 'customer_name',
            self::CUSTOMER_URL => 'customer_url',
        ]);
        $this->assertEquals('customer_name', $service->getCustomerName());
        $this->assertEquals('customer_url', $service->getCustomerUrl());

        $this->assertNull($service->getLastImport());
        $this->assertNull($service->getLastUpdate());

        $this->assertNull($service->getDefaultState());
        $this->assertEquals(0, $service->getDefaultStateId());

        $this->assertEquals(3.0, $service->getMinMargin());
        $this->assertEquals(-1, $service->getMinStrength());
        $this->assertEquals(4000, $service->getMessageTimeout());
    }

    protected function echo(string $name, $value): void
    {
        echo \sprintf("\n%-15s: %s", $name, $value);
    }
}
