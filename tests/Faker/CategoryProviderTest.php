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

use App\Entity\Category;
use App\Faker\CategoryProvider;
use App\Faker\Factory;
use App\Repository\CategoryRepository;
use App\Utils\FormatUtils;
use PHPUnit\Framework\TestCase;

final class CategoryProviderTest extends TestCase
{
    public function testWithEntity(): void
    {
        $entity = new Category();
        $entity->setCode('code');
        $provider = $this->createProvider($entity);

        $actual = \count($provider);
        self::assertSame(1, $actual);

        $actual = $provider->category();
        self::assertSame($entity, $actual);
    }

    public function testWithoutEntity(): void
    {
        $provider = $this->createProvider();

        $actual = \count($provider);
        self::assertSame(0, $actual);

        $actual = $provider->category();
        self::assertNull($actual);
    }

    private function createProvider(?Category $entity = null): CategoryProvider
    {
        $entities = $entity instanceof Category ? [$entity] : [];
        $repository = $this->createMock(CategoryRepository::class);
        $repository->method('findBy')
            ->willReturn($entities);
        $repository->method('findAll')
            ->willReturn($entities);

        $generator = Factory::create(FormatUtils::DEFAULT_LOCALE);

        return new CategoryProvider($generator, $repository);
    }
}
