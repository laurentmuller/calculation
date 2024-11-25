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
use App\Repository\UserRepository;
use App\Utils\FormatUtils;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class CalculationStateProviderTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testWithEntity(): void
    {
        $entity = new CalculationState();
        $entity->setCode('code');
        $provider = $this->createProvider($entity);

        $actual = $provider->statesCount();
        self::assertSame(1, $actual);

        $actual = $provider->state();
        self::assertSame($entity, $actual);
    }

    /**
     * @throws Exception
     */
    public function testWithoutEntity(): void
    {
        $provider = $this->createProvider();

        $actual = $provider->statesCount();
        self::assertSame(0, $actual);

        $actual = $provider->state();
        self::assertNull($actual);
    }

    /**
     * @throws Exception
     */
    private function createProvider(?CalculationState $entity = null): CalculationStateProvider
    {
        $entities = $entity instanceof CalculationState ? [$entity] : [];
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findBy')
            ->willReturn($entities);

        $repository->method('findAll')
            ->willReturn($entities);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')
            ->willReturn($repository);

        $generator = Factory::create(FormatUtils::DEFAULT_LOCALE);

        return new CalculationStateProvider($generator, $manager);
    }
}
