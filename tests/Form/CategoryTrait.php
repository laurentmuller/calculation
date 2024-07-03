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
use App\Repository\CategoryRepository;
use App\Tests\Entity\IdTrait;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * @psalm-require-extends TestCase
 */
trait CategoryTrait
{
    use GroupTrait;
    use IdTrait;
    use ManagerRegistryTrait;

    private ?Category $category = null;

    /**
     * @throws \ReflectionException
     */
    protected function getCategory(): Category
    {
        if (!$this->category instanceof Category) {
            $this->category = new Category();
            $this->category->setCode('category');
            $this->getGroup()->addCategory($this->category);

            return self::setId($this->category);
        }

        return $this->category;
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getEntityType(): EntityType
    {
        return new EntityType($this->getRegistry());
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getRegistry(): MockObject&ManagerRegistry
    {
        $query = $this->createQuery([$this->getCategory()]);
        $builder = $this->createQueryBuilder($query);
        $manager = $this->createEntityManager(Category::class);
        $repository = $this->createRepository(CategoryRepository::class);
        $registry = $this->createRegistry($manager);

        $repository->method('getQueryBuilderByGroup')
            ->willReturn($builder);

        $manager->method('getRepository')
            ->willReturn($repository);

        return $registry;
    }
}
