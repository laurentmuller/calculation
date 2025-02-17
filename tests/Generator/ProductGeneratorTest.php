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

namespace App\Tests\Generator;

use App\Faker\Generator;
use App\Generator\ProductGenerator;
use App\Service\FakerService;
use App\Tests\EntityTrait\CategoryTrait;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends GeneratorTestCase<ProductGenerator>
 */
class ProductGeneratorTest extends GeneratorTestCase
{
    use CategoryTrait;

    public function testNegativeCount(): void
    {
        $generator = $this->createGenerator();
        $actual = $generator->generate(-1, true);
        self::assertValidateResponse($actual, false, 0);
    }

    public function testNotSimulate(): void
    {
        $this->getCategory();
        $generator = $this->createGenerator();
        $actual = $generator->generate(1, false);
        self::assertValidateResponse($actual, true, 1);
    }

    public function testOne(): void
    {
        $generator = $this->createGenerator();
        $actual = $generator->generate(1, true);
        self::assertValidateResponse($actual, true, 1);
    }

    public function testProductExist(): void
    {
        $count = 0;
        $manager = $this->createMock(EntityManagerInterface::class);
        $generator = $this->createMock(Generator::class);
        $generator->method('__call')
            ->willReturnCallback(function (string $name) use (&$count): string|bool|null {
                return match ($name) {
                    'productName' => 'Fake Name',
                    'productExist' => 1 === ++$count,
                    default => null,
                };
            });
        $fakerService = $this->createMock(FakerService::class);
        $fakerService->method('getGenerator')
            ->willReturn($generator);

        $productGenerator = new class($manager, $fakerService) extends ProductGenerator {
            #[\Override]
            public function createEntities(int $count, bool $simulate, Generator $generator): array
            {
                return parent::createEntities($count, $simulate, $generator);
            }
        };
        $actual = $productGenerator->createEntities(1, true, $generator);
        self::assertCount(1, $actual);
    }

    #[\Override]
    protected function createGenerator(): ProductGenerator
    {
        $generator = new ProductGenerator($this->manager, $this->fakerService);

        return $this->updateGenerator($generator);
    }
}
