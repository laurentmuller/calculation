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

use App\Faker\CalculationStateProvider;
use App\Faker\CategoryProvider;
use App\Faker\CustomAddress;
use App\Faker\CustomCompany;
use App\Faker\CustomPerson;
use App\Faker\CustomPhoneNumber;
use App\Faker\ProductProvider;
use App\Faker\UserProvider;
use App\Service\FakerService;
use App\Tests\KernelServiceTestCase;

class FakerServiceTest extends KernelServiceTestCase
{
    public function testProviders(): void
    {
        $service = $this->getService(FakerService::class);
        $generator = $service->getGenerator();
        /** @psalm-var object[] $providers */
        $providers = $generator->getProviders();
        self::assertNotEmpty($providers);
        self::assertProviderExist($providers, CustomPerson::class);
        self::assertProviderExist($providers, CustomCompany::class);
        self::assertProviderExist($providers, CustomAddress::class);
        self::assertProviderExist($providers, CustomPhoneNumber::class);
        self::assertProviderExist($providers, UserProvider::class);
        self::assertProviderExist($providers, ProductProvider::class);
        self::assertProviderExist($providers, CategoryProvider::class);
        self::assertProviderExist($providers, CalculationStateProvider::class);
    }

    /**
     * @psalm-param object[] $providers
     */
    protected static function assertProviderExist(array $providers, string $class): void
    {
        foreach ($providers as $provider) {
            if ($provider::class === $class) {
                return;
            }
        }
        self::fail("Unable to find the $class provider");
    }
}
