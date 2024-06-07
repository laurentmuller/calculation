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
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractRepository::class)]
#[CoversClass(CalculationGroupRepository::class)]
#[CoversClass(CalculationCategoryRepository::class)]
#[CoversClass(CalculationItemRepository::class)]
#[CoversClass(TaskItemRepository::class)]
#[CoversClass(TaskItemMarginRepository::class)]
class OtherRepositoriesTest extends KernelServiceTestCase
{
    use DatabaseTrait;
    use IdTrait;

    /**
     * @throws \ReflectionException
     * @throws ORMException
     */
    public function testCountCategoryReferences(): void
    {
        $category = new Category();
        self::setId($category);
        $repository = $this->getService(CalculationCategoryRepository::class);
        $actual = $repository->countCategoryReferences($category);
        self::assertSame(0, $actual);
    }

    /**
     * @throws \ReflectionException
     * @throws ORMException
     */
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
        $this->findAll(CalculationGroupRepository::class);
        $this->findAll(CalculationCategoryRepository::class);
        $this->findAll(CalculationItemRepository::class);

        $this->findAll(TaskItemRepository::class);
        $this->findAll(TaskItemMarginRepository::class);
    }

    /**
     * @template TRepository of AbstractRepository
     *
     * @param class-string<TRepository> $class
     */
    protected function findAll(string $class): void
    {
        /** @psalm-var AbstractRepository<EntityInterface> $repository */
        $repository = $this->getService($class);
        $actual = $repository->findAll();
        self::assertCount(0, $actual);
    }
}
