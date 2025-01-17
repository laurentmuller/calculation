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
use App\Faker\Generator;
use App\Faker\ProductProvider;
use App\Faker\UserProvider;
use App\Service\FakerService;
use App\Tests\KernelServiceTestCase;
use Faker\Provider\Base;

class FakerServiceTest extends KernelServiceTestCase
{
    public function testProviders(): void
    {
        $service = $this->getService(FakerService::class);
        $generator = $service->getGenerator();

        self::assertProviderExist($generator, CustomPerson::class);
        self::assertProviderExist($generator, CustomCompany::class);
        self::assertProviderExist($generator, CustomAddress::class);
        self::assertProviderExist($generator, CustomPhoneNumber::class);
        self::assertProviderExist($generator, UserProvider::class);
        self::assertProviderExist($generator, ProductProvider::class);
        self::assertProviderExist($generator, CategoryProvider::class);
        self::assertProviderExist($generator, CalculationStateProvider::class);
    }

    /**
     * @template TProvider of Base
     *
     * @param class-string<TProvider> $class
     */
    protected static function assertProviderExist(Generator $generator, string $class): void
    {
        $provider = $generator->getProvider($class);
        self::assertNotNull($provider);
        self::assertInstanceOf($class, $provider);
    }
}
