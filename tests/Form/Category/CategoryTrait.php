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

namespace App\Tests\Form\Category;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Tests\Entity\IdTrait;
use App\Tests\Form\Group\GroupTrait;
use App\Tests\Form\ManagerRegistryTrait;
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
    protected function getCategoryEntityType(): EntityType
    {
        return new EntityType($this->getCategoryRegistry());
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getCategoryRegistry(): MockObject&ManagerRegistry
    {
        return $this->createManagerRegistry(
            Category::class,
            CategoryRepository::class,
            'getQueryBuilderByGroup',
            [$this->getCategory()]
        );
    }
}
