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

use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use App\Faker\CalculationStateProvider;
use App\Faker\CategoryProvider;
use App\Faker\CustomAddress;
use App\Faker\CustomCompany;
use App\Faker\CustomPerson;
use App\Faker\CustomPhoneNumber;
use App\Faker\Generator;
use App\Faker\ProductProvider;
use App\Faker\UserProvider;
use App\Repository\AbstractRepository;
use App\Repository\CalculationStateRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Service\FakerService;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Provider\Base;
use PHPUnit\Framework\TestCase;

final class FakerServiceTest extends TestCase
{
    public function testProviders(): void
    {
        $manager = $this->createEntityManager();
        $service = new FakerService($manager);
        $generator = $service->getGenerator();

        $this->assertProviderExist($generator, CustomPerson::class);
        $this->assertProviderExist($generator, CustomCompany::class);
        $this->assertProviderExist($generator, CustomAddress::class);
        $this->assertProviderExist($generator, CustomPhoneNumber::class);
        $this->assertProviderExist($generator, UserProvider::class);
        $this->assertProviderExist($generator, ProductProvider::class);
        $this->assertProviderExist($generator, CategoryProvider::class);
        $this->assertProviderExist($generator, CalculationStateProvider::class);
    }

    /**
     * @template TProvider of Base
     *
     * @param class-string<TProvider> $class
     */
    private function assertProviderExist(Generator $generator, string $class): void
    {
        $provider = $generator->getProvider($class);
        self::assertNotNull($provider);
        self::assertInstanceOf($class, $provider);
    }

    private function createEntityManager(): EntityManagerInterface
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')
            ->willReturnCallback(fn (string $className): AbstractRepository => match ($className) {
                User::class => $this->createMock(UserRepository::class),
                Product::class => $this->createMock(ProductRepository::class),
                Category::class => $this->createMock(CategoryRepository::class),
                CalculationState::class => $this->createMock(CalculationStateRepository::class),
                default => throw new \LogicException('Unexpected repository: ' . $className),
            });

        return $manager;
    }
}
