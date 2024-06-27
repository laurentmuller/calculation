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

namespace App\Tests\Form;

use App\Entity\Category;
use App\Entity\Product;
use App\Form\Product\ProductType;
use App\Repository\CategoryRepository;
use App\Tests\Entity\IdTrait;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\PreloadedExtension;

/**
 * @extends EntityTypeTestCase<Product, ProductType>
 */
#[CoversClass(ProductType::class)]
class ProductTypeTest extends EntityTypeTestCase
{
    use IdTrait;

    private static ?Category $category = null;

    protected function getData(): array
    {
        return [
            'description' => 'description',
            'unit' => 'unit',
            'price' => 1.25,
            'category' => null,
            'supplier' => 'supplier',
        ];
    }

    protected function getEntityClass(): string
    {
        return Product::class;
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getExtensions(): array
    {
        /** @psalm-var \Symfony\Component\Form\FormExtensionInterface[] $extensions */
        $extensions = parent::getExtensions();

        $registry = $this->getRegistry();
        $type = new EntityType($registry);
        $extensions[] = new PreloadedExtension([$type], []);

        return $extensions;
    }

    protected function getFormTypeClass(): string
    {
        return ProductType::class;
    }

    /**
     * @throws \ReflectionException
     */
    private function getCategory(): Category
    {
        if (!self::$category instanceof Category) {
            self::$category = new Category();
            self::$category->setCode('category');

            return self::setId(self::$category);
        }

        return self::$category;
    }

    /**
     * @throws Exception|\ReflectionException
     */
    private function getRegistry(): MockObject&ManagerRegistry
    {
        $query = $this->createQuery();
        $builder = $this->createQueryBuilder($query);
        $manager = $this->createEntityManager();
        $repository = $this->createRepository(CategoryRepository::class);
        $registry = $this->createRegistry($manager);

        $query->method('execute')
            ->willReturn([$this->getCategory()]);

        $repository->method('getQueryBuilderByGroup')
            ->willReturn($builder);

        $manager->method('getRepository')
            ->willReturn($repository);

        return $registry;
    }
}
