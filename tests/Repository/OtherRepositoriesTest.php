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

namespace App\Tests\Repository;

use App\Entity\Category;
use App\Entity\Group;
use App\Interfaces\EntityInterface;
use App\Repository\AbstractRepository;
use App\Repository\CalculationCategoryRepository;
use App\Repository\CalculationGroupRepository;
use App\Repository\CalculationItemRepository;
use App\Repository\TaskItemMarginRepository;
use App\Repository\TaskItemRepository;
use App\Tests\DatabaseTrait;
use App\Tests\Entity\IdTrait;
use App\Tests\KernelServiceTestCase;

final class OtherRepositoriesTest extends KernelServiceTestCase
{
    use DatabaseTrait;
    use IdTrait;

    public function testCountCategoryReferences(): void
    {
        $category = new Category();
        self::setId($category);
        $repository = $this->getService(CalculationCategoryRepository::class);
        $actual = $repository->countCategoryReferences($category);
        self::assertSame(0, $actual);
    }

    public function testCountGroupReferences(): void
    {
        $group = new Group();
        self::setId($group);
        $repository = $this->getService(CalculationGroupRepository::class);
        $actual = $repository->countGroupReferences($group);
        self::assertSame(0, $actual);
    }

    public function testFindAll(): void
    {
        $this->assertFindAllIsEmpty(CalculationGroupRepository::class);
        $this->assertFindAllIsEmpty(CalculationCategoryRepository::class);
        $this->assertFindAllIsEmpty(CalculationItemRepository::class);

        $this->assertFindAllIsEmpty(TaskItemRepository::class);
        $this->assertFindAllIsEmpty(TaskItemMarginRepository::class);
    }

    /**
     * @template TEntity of EntityInterface
     *
     * @param class-string<AbstractRepository<TEntity>> $class
     */
    private function assertFindAllIsEmpty(string $class): void
    {
        $repository = $this->getService($class);
        $actual = $repository->findAll();
        self::assertEmpty($actual);
    }
}
