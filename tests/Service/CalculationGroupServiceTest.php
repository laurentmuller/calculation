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

namespace App\Tests\Service;

use App\Entity\Calculation;
use App\Entity\Category;
use App\Entity\GlobalMargin;
use App\Entity\Group;
use App\Entity\GroupMargin;
use App\Entity\Product;
use App\Interfaces\EntityInterface;
use App\Model\CalculationAdjustQuery;
use App\Repository\GlobalMarginRepository;
use App\Repository\GroupMarginRepository;
use App\Repository\GroupRepository;
use App\Service\ApplicationService;
use App\Service\CalculationGroupService;
use App\Tests\DatabaseTrait;
use App\Tests\Entity\IdTrait;
use App\Tests\KernelServiceTestCase;
use App\Tests\TranslatorMockTrait;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;

class CalculationGroupServiceTest extends KernelServiceTestCase
{
    use DatabaseTrait;
    use IdTrait;
    use TranslatorMockTrait;

    private const MARGIN_PERCENT = 1.1;
    private const MARGIN_USER = 0.1;
    private const PRODUCT_PRICE = 100.0;
    private const QUANTITY = 10.0;

    /**
     * @throws \ReflectionException
     */
    public function testAdjustUserMargin(): void
    {
        $query = new CalculationAdjustQuery(
            adjust: true,
            userMargin: -0.99,
            groups: [
                ['id' => 1, 'total' => 100.0],
            ]
        );

        $groupMargin = new GroupMargin();
        $groupMargin->setMaximum(1000.0)
            ->setMargin(0.02);
        $group = $this->createGroup();
        $group->addMargin($groupMargin);
        $service = $this->createCalculationService($group, 1.0, 1.1);
        $actual = $service->createParameters($query);
        self::assertCount(7, $actual);
    }

    public function testCreateGroupsFromCalculation(): void
    {
        self::bootKernel();

        $product = $this->init();
        $calculation = new Calculation();

        $service = $this->createCalculationService();
        $actual = $service->createGroups($calculation);
        self::assertCount(1, $actual);
        self::assertArrayHasKey(0, $actual);

        $row = $actual[0];
        self::assertSame(-1, $row['id']);
        self::assertSame(0.0, $row['amount']);
        self::assertSame(0.0, $row['margin_percent']);
        self::assertSame(0.0, $row['margin_amount']);
        self::assertSame(0.0, $row['total']);

        $calculation = new Calculation();
        $calculation->addProduct($product, self::QUANTITY);
        $actual = $service->createGroups($calculation);
        self::assertCount(6, $actual);

        $calculation = new Calculation();
        $calculation->addProduct($product, self::QUANTITY)
            ->setUserMargin(self::MARGIN_USER);
        $actual = $service->createGroups($calculation);
        self::assertCount(6, $actual);
    }

    public function testCreateGroupsFromDataEmpty(): void
    {
        $query = new CalculationAdjustQuery();
        $service = $this->createCalculationService();
        $actual = $service->createParameters($query);
        self::assertCount(7, $actual);
        self::assertCount(1, $actual['groups']);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCreateGroupsFromQuery(): void
    {
        $query = new CalculationAdjustQuery(
            adjust: false,
            userMargin: 0.05,
            groups: [
                ['id' => 1, 'total' => 2.5],
            ]
        );

        $group = $this->createGroup();
        $service = $this->createCalculationService($group);
        $actual = $service->createParameters($query);
        self::assertCount(7, $actual);
        self::assertCount(6, $actual['groups']);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCreateGroupsFromQueryEmpty(): void
    {
        $query = new CalculationAdjustQuery(
            adjust: false,
            userMargin: 0.05,
            groups: []
        );

        $group = $this->createGroup();
        $service = $this->createCalculationService($group);
        $actual = $service->createParameters($query);
        self::assertCount(7, $actual);
        self::assertCount(1, $actual['groups']);
    }

    public function testCreateGroupsFromQueryGroupNotFound(): void
    {
        $query = new CalculationAdjustQuery(
            adjust: false,
            userMargin: 0.05,
            groups: [
                ['id' => 10, 'total' => 10.0],
            ]
        );

        $service = $this->createCalculationService();
        $actual = $service->createParameters($query);
        self::assertCount(7, $actual);
        self::assertCount(1, $actual['groups']);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCreateGroupsFromQueryGroupTotalZero(): void
    {
        $query = new CalculationAdjustQuery(
            adjust: false,
            userMargin: 0.05,
            groups: [
                ['id' => 1, 'total' => 0.0],
            ]
        );

        $group = $this->createGroup();
        $service = $this->createCalculationService($group);
        $actual = $service->createParameters($query);
        self::assertCount(7, $actual);
        self::assertCount(1, $actual['groups']);
    }

    public function testGetConstants(): void
    {
        $constants = CalculationGroupService::constants();
        self::assertCount(7, $constants);

        self::assertArrayHasKey('ROW_EMPTY', $constants);
        self::assertArrayHasKey('ROW_GROUP', $constants);
        self::assertArrayHasKey('ROW_TOTAL_GROUP', $constants);
        self::assertArrayHasKey('ROW_GLOBAL_MARGIN', $constants);
        self::assertArrayHasKey('ROW_TOTAL_NET', $constants);
        self::assertArrayHasKey('ROW_USER_MARGIN', $constants);
        self::assertArrayHasKey('ROW_OVERALL_TOTAL', $constants);

        self::assertSame(-1, $constants['ROW_EMPTY']);
        self::assertSame(-2, $constants['ROW_GROUP']);
        self::assertSame(-3, $constants['ROW_TOTAL_GROUP']);
        self::assertSame(-4, $constants['ROW_GLOBAL_MARGIN']);
        self::assertSame(-5, $constants['ROW_TOTAL_NET']);
        self::assertSame(-6, $constants['ROW_USER_MARGIN']);
        self::assertSame(-7, $constants['ROW_OVERALL_TOTAL']);
    }

    protected function createCalculationService(
        ?Group $group = null,
        ?float $groupMargin = null,
        ?float $globalMargin = null,
    ): CalculationGroupService {
        $globalMarginRepository = $this->createGlobalMarginRepository($globalMargin);
        $groupMarginRepository = $this->createGroupMarginRepository($groupMargin);
        $groupRepository = $this->createGroupRepository($group);
        $application = $this->getService(ApplicationService::class);
        $translator = $this->createMockTranslator();

        return new CalculationGroupService(
            $globalMarginRepository,
            $groupMarginRepository,
            $groupRepository,
            $application,
            $translator
        );
    }

    protected function echo(string $name, mixed $value): void
    {
        if (\is_scalar($value) || (\is_object($value) && \method_exists($value, '__toString'))) {
            echo \sprintf("\n%-15s: %s", $name, (string) $value);
        }
    }

    protected function init(): Product
    {
        $manager = $this->getManager();
        $this->initGlobalMargins($manager);
        $category = $this->initCategories($manager);

        return $this->initProducts($manager, $category);
    }

    protected function initCategories(ObjectManager $manager): Category
    {
        $this->initRepository($manager, GroupMargin::class);
        $this->initRepository($manager, Category::class);
        $this->initRepository($manager, Group::class);

        $group = new Group();
        $group->setCode('Test');

        $margin = new GroupMargin();
        $margin->setMinimum(0)
            ->setMaximum(1_000_000)
            ->setMargin(self::MARGIN_PERCENT);
        $group->addMargin($margin);

        $category = new Category();
        $category->setCode('Test')
            ->setGroup($group);

        $manager->persist($group);
        $manager->persist($margin);
        $manager->persist($category);
        $manager->flush();

        return $category;
    }

    protected function initGlobalMargins(ObjectManager $manager): void
    {
        $this->initRepository($manager, GlobalMargin::class);

        $margin = new GlobalMargin();
        $margin->setMinimum(0)
            ->setMaximum(1_000_000)
            ->setMargin(self::MARGIN_PERCENT);
        $manager->persist($margin);

        $manager->flush();
    }

    protected function initProducts(ObjectManager $manager, Category $category): Product
    {
        $this->initRepository($manager, Product::class);

        $product = new Product();
        $product->setDescription('Product Test')
            ->setPrice(self::PRODUCT_PRICE)
            ->setCategory($category);

        $manager->persist($product);
        $manager->flush();

        return $product;
    }

    /**
     * @phpstan-template TEntity of EntityInterface
     *
     * @phpstan-param class-string<TEntity> $entityName
     *
     * @phpstan-return EntityRepository<TEntity> $repository
     */
    protected function initRepository(ObjectManager $manager, string $entityName): EntityRepository
    {
        /** @phpstan-var \App\Repository\AbstractRepository<TEntity> $repository */
        $repository = $manager->getRepository($entityName);

        $items = $repository->findAll();
        foreach ($items as $item) {
            $manager->remove($item);
        }
        $manager->flush();

        return $repository;
    }

    private function createGlobalMarginRepository(?float $globalMargin = null): GlobalMarginRepository
    {
        if (null === $globalMargin) {
            return $this->getService(GlobalMarginRepository::class);
        }
        $repository = $this->createMock(GlobalMarginRepository::class);
        $repository->method('getMargin')
            ->willReturn($globalMargin);

        return $repository;
    }

    /**
     * @throws \ReflectionException
     */
    private function createGroup(): Group
    {
        $group = new Group();

        return self::setId($group);
    }

    private function createGroupMarginRepository(?float $groupMargin = null): GroupMarginRepository
    {
        if (null === $groupMargin) {
            return $this->getService(GroupMarginRepository::class);
        }
        $repository = $this->createMock(GroupMarginRepository::class);
        $repository->method('getMargin')
            ->willReturn($groupMargin);

        return $repository;
    }

    private function createGroupRepository(?Group $group = null): GroupRepository
    {
        if (!$group instanceof Group) {
            return $this->getService(GroupRepository::class);
        }
        $repository = $this->createMock(GroupRepository::class);
        $repository->method('find')
            ->willReturn($group);

        return $repository;
    }
}
