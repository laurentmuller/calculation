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
use App\Repository\GroupRepository;
use App\Tests\DatabaseTrait;
use App\Tests\EntityTrait\CategoryTrait;
use App\Tests\KernelServiceTestCase;
use Doctrine\ORM\QueryBuilder;

final class GroupRepositoryTest extends KernelServiceTestCase
{
    use CategoryTrait;
    use DatabaseTrait;

    private GroupRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(GroupRepository::class);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->deleteGroup();
        parent::tearDown();
    }

    public function testFindByCode(): void
    {
        $actual = $this->repository->findByCode();
        self::assertEmpty($actual);

        $this->getGroup();
        $actual = $this->repository->findByCode();
        self::assertCount(1, $actual);
    }

    public function testGetDropDown(): void
    {
        $actual = $this->repository->getDropDown();
        self::assertEmpty($actual);

        $this->getCategory();
        $actual = $this->repository->getDropDown();
        self::assertEmpty($actual);

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
