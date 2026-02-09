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
use App\Tests\TranslatorMockTrait;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends GeneratorTestCase<ProductGenerator>
 */
final class ProductGeneratorTest extends GeneratorTestCase
{
    use CategoryTrait;
    use TranslatorMockTrait;

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
        $manager = self::createStub(EntityManagerInterface::class);
        $generator = $this->createMock(Generator::class);
        $generator->method('__call')
            ->willReturnCallback(static function (string $name) use (&$count): string|bool|null {
                return match ($name) {
                    'productName' => 'Fake Name',
                    'productExist' => 1 === ++$count,
                    default => null,
                };
            });
        $service = $this->createMock(FakerService::class);
        $service->method('getGenerator')
            ->willReturn($generator);
        $translator = $this->createMockTranslator();
        $logger = $this->createMockLogger();

        $productGenerator = new class($service, $manager, $translator, $logger) extends ProductGenerator {
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
        return new ProductGenerator(
            service: $this->service,
            manager: $this->manager,
            translator: $this->createMockTranslator(),
            logger: $this->createMockLogger()
        );
    }
}
