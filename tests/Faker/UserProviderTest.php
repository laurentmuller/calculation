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

use App\Entity\User;
use App\Faker\Factory;
use App\Faker\UserProvider;
use App\Repository\UserRepository;
use App\Utils\FormatUtils;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class UserProviderTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testWithEntity(): void
    {
        $entity = new User();
        $entity->setUsername('user_name');
        $provider = $this->createProvider($entity);

        $actual = \count($provider);
        self::assertSame(1, $actual);

        $actual = $provider->user();
        self::assertSame($entity, $actual);

        $actual = $provider->userName();
        self::assertNotNull($actual);
        self::assertSame('user_name', $actual);
    }

    /**
     * @throws Exception
     */
    public function testWithoutEntity(): void
    {
        $provider = $this->createProvider();

        $actual = \count($provider);
        self::assertSame(0, $actual);

        $actual = $provider->user();
        self::assertNull($actual);

        $actual = $provider->userName();
        self::assertNull($actual);
    }

    /**
     * @throws Exception
     */
    private function createProvider(?User $entity = null): UserProvider
    {
        $entities = $entity instanceof User ? [$entity] : [];
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findBy')
            ->willReturn($entities);
        $repository->method('findAll')
            ->willReturn($entities);

        $generator = Factory::create(FormatUtils::DEFAULT_LOCALE);

        return new UserProvider($generator, $repository);
    }
}
