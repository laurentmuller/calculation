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
use App\Entity\Product;
use App\Faker\Factory;
use App\Faker\ProductProvider;
use App\Repository\ProductRepository;
use App\Utils\FormatUtils;
use PHPUnit\Framework\TestCase;

class ProductProviderTest extends TestCase
{
    public function testProductName(): void
    {
        $provider = $this->createProvider();
        $actual = $provider->productName();
        self::assertNotEmpty($actual);
    }

    public function testProductsEmpty(): void
    {
        $provider = $this->createProvider();
        $actual = $provider->products();
        self::assertEmpty($actual);
    }

    public function testProductsWithOneProduct(): void
    {
        $entity = new Product();
        $entity->setDescription('description')
            ->setSupplier('supplier')
            ->setUnit('unit');
        $provider = $this->createProvider($entity);
        $actual = $provider->products();
        self::assertCount(1, $actual);
    }

    public function testProductsWithTwoCategories(): void
    {
        $category1 = new Category();
        $category1->setCode('code 1');
        $entity1 = new Product();
        $entity1->setDescription('description 1')
            ->setCategory($category1);

        $category2 = new Category();
        $category2->setCode('code 2');
        $entity2 = new Product();
        $entity2->setDescription('description 2')
            ->setCategory($category2);

        $provider = $this->createProvider($entity1, $entity2);
        $actual = $provider->products(2);
        self::assertCount(2, $actual);
    }

    public function testProductsWithTwoProducts(): void
    {
        $category = new Category();
        $category->setCode('code');

        $entity1 = new Product();
        $entity1->setDescription('description 1')
            ->setCategory($category);
        $entity2 = new Product();
        $entity2->setDescription('description 2')
            ->setCategory($category);

        $provider = $this->createProvider($entity1, $entity2);
        $actual = $provider->products(2);
        self::assertCount(2, $actual);
    }

    public function testWithEntity(): void
    {
        $entity = new Product();
        $entity->setDescription('description')
            ->setSupplier('supplier')
            ->setUnit('unit');
        $provider = $this->createProvider($entity);

        $actual = \count($provider);
        self::assertSame(1, $actual);

        $actual = $provider->product();
        self::assertSame($entity, $actual);

        $actual = $provider->productExist('description');
        self::assertTrue($actual);
    }

    public function testWithoutEntity(): void
    {
        $provider = $this->createProvider();

        $actual = \count($provider);
        self::assertSame(0, $actual);

        $actual = $provider->product();
        self::assertNull($actual);

        $actual = $provider->productSupplier();
        self::assertNull($actual);

        $actual = $provider->productUnit();
        self::assertNull($actual);

        $actual = $provider->productExist('fake');
        self::assertFalse($actual);
    }

    private function createProvider(Product ...$entities): ProductProvider
    {
        $entity = [] === $entities ? null : $entities[0];
        $values = [] === $entities ? [] : \array_map(fn (
            Product $product
        ): string => (string) $product->getDescription(), $entities);

        $repository = $this->createMock(ProductRepository::class);
        $repository->method('findBy')
            ->willReturn($entities);
        $repository->method('findOneBy')
            ->willReturn($entity);
        $repository->method('findAll')
            ->willReturn($entities);
        $repository->method('getDistinctValues')
            ->willReturn($values);

        $generator = Factory::create(FormatUtils::DEFAULT_LOCALE);

        return new ProductProvider($generator, $repository);
    }
}
