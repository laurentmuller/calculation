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

namespace App\Tests\Faker;

use App\Entity\CalculationState;
use App\Faker\CalculationStateProvider;
use App\Faker\Factory;
use App\Repository\CalculationStateRepository;
use App\Utils\FormatUtils;
use PHPUnit\Framework\TestCase;

final class CalculationStateProviderTest extends TestCase
{
    public function testWithEntity(): void
    {
        $entity = new CalculationState();
        $entity->setCode('code');
        $provider = $this->createProvider($entity);

        $actual = \count($provider);
        self::assertSame(1, $actual);

        $actual = $provider->state();
        self::assertSame($entity, $actual);
    }

    public function testWithoutEntity(): void
    {
        $provider = $this->createProvider();

        $actual = \count($provider);
        self::assertSame(0, $actual);

        $actual = $provider->state();
        self::assertNull($actual);
    }

    private function createProvider(?CalculationState $entity = null): CalculationStateProvider
    {
        $entities = $entity instanceof CalculationState ? [$entity] : [];
        $repository = $this->createMock(CalculationStateRepository::class);
        $repository->method('findBy')
            ->willReturn($entities);
        $repository->method('findAll')
            ->willReturn($entities);

        $generator = Factory::create(FormatUtils::DEFAULT_LOCALE);

        return new CalculationStateProvider($generator, $repository);
    }
}
