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
use App\Repository\AbstractRepository;
use App\Repository\GroupRepository;
use App\Tests\DatabaseTrait;
use App\Tests\EntityTrait\CategoryTrait;
use App\Tests\KernelServiceTestCase;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractRepository::class)]
#[CoversClass(GroupRepository::class)]
class GroupRepositoryTest extends KernelServiceTestCase
{
    use CategoryTrait;
    use DatabaseTrait;

    private GroupRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(GroupRepository::class);
    }

    /**
     * @throws ORMException
     */
    protected function tearDown(): void
    {
        $this->deleteGroup();
        parent::tearDown();
    }

    /**
     * @throws ORMException
     */
    public function testFindByCode(): void
    {
        $actual = $this->repository->findByCode();
        self::assertCount(0, $actual);

        $this->getGroup();
        $actual = $this->repository->findByCode();
        self::assertCount(1, $actual);
    }

    /**
     * @throws ORMException
     */
    public function testGetDropDown(): void
    {
        $actual = $this->repository->getDropDown();
        self::assertCount(0, $actual);

        $this->getCategory();
        $actual = $this->repository->getDropDown();
        self::assertCount(0, $actual);

        $group = new Group();
        $group->setCode('My Code');
        $this->addEntity($group);

        $category = new Category();
        $category->setCode('My Code')
            ->setGroup($group);
        $this->addEntity($category);

        try {
            $actual = $this->repository->getDropDown();
            self::assertCount(2, $actual);
        } finally {
            $this->deleteEntity($category);
            $this->deleteEntity($group);
        }
    }

    public function testGetSortedBuilder(): void
    {
        $actual = $this->repository->getSortedBuilder();
        self::assertInstanceOf(QueryBuilder::class, $actual);
    }

    public function testGetTableQueryBuilder(): void
    {
        $actual = $this->repository->getTableQueryBuilder();
        self::assertInstanceOf(QueryBuilder::class, $actual);
    }
}
