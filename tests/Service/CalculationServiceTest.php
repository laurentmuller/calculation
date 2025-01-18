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
use App\Entity\CalculationCategory;
use App\Entity\CalculationGroup;
use App\Entity\CalculationItem;
use App\Entity\Category;
use App\Entity\GlobalMargin;
use App\Entity\Group;
use App\Entity\GroupMargin;
use App\Entity\Product;
use App\Interfaces\EntityInterface;
use App\Model\CalculationGroupQuery;
use App\Model\CalculationQuery;
use App\Repository\GlobalMarginRepository;
use App\Repository\GroupMarginRepository;
use App\Repository\GroupRepository;
use App\Service\ApplicationService;
use App\Service\CalculationService;
use App\Tests\DatabaseTrait;
use App\Tests\Entity\IdTrait;
use App\Tests\KernelServiceTestCase;
use App\Tests\TranslatorMockTrait;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\MockObject\Exception;

class CalculationServiceTest extends KernelServiceTestCase
{
    use DatabaseTrait;
    use IdTrait;
    use TranslatorMockTrait;

    private const MARGIN_PERCENT = 1.1;
    private const MARGIN_USER = 0.1;
    private const PRODUCT_PRICE = 100.0;
    private const QUANTITY = 10.0;

    /**
     * @throws Exception
     * @throws ORMException|\ReflectionException
     */
    public function testAdjustUserMargin(): void
    {
        $query = new CalculationQuery(
            adjust: true,
            userMargin: -0.99,
            groups: [
                new CalculationGroupQuery(1, 100.0),
            ]
        );

        $groupMargin = new GroupMargin();
        $groupMargin->setMaximum(1000.0)
            ->setMargin(0.02);
        $group = $this->createGroup();
        $group->addMargin($groupMargin);
        $service = $this->createCalculationService($group, 1.0, 1.1);
        $actual = $service->createGroupsFromQuery($query);
        self::assertCount(7, $actual);
    }

    /**
     * @throws ORMException|Exception
     */
    public function testCreateGroupsFromCalculation(): void
    {
        self::bootKernel();

        $product = $this->init();
        $calculation = new Calculation();

        $service = $this->createCalculationService();
        $actual = $service->createGroupsFromCalculation($calculation);
        self::assertCount(1, $actual);
        self::assertArrayHasKey(0, $actual);

        $row = $actual[0];
        self::assertArrayHasKey('id', $row);
        self::assertSame(0, $row['id']);
        self::assertSame(0.0, $row['amount']);
        self::assertSame(0.0, $row['margin']);
        self::assertSame(0.0, $row['margin_amount']);
        self::assertSame(0.0, $row['total']);

        $calculation = new Calculation();
        $calculation->addProduct($product, self::QUANTITY);
        $actual = $service->createGroupsFromCalculation($calculation);
        self::assertCount(6, $actual);

        $calculation = new Calculation();
        $calculation->addProduct($product, self::QUANTITY)
            ->setUserMargin(self::MARGIN_USER);
        $actual = $service->createGroupsFromCalculation($calculation);
        self::assertCount(6, $actual);
    }

    /**
     * @throws Exception|ORMException
     */
    public function testCreateGroupsFromDataEmpty(): void
    {
        $query = new CalculationQuery();
        $service = $this->createCalculationService();
        $actual = $service->createGroupsFromQuery($query);
        self::assertCount(7, $actual);
        self::assertArrayHasKey('groups', $actual);
        self::assertCount(1, $actual['groups']);
    }

    /**
     * @throws Exception|ORMException|\ReflectionException
     */
    public function testCreateGroupsFromQuery(): void
    {
        $query = new CalculationQuery(
            adjust: false,
            userMargin: 0.05,
            groups: [
                new CalculationGroupQuery(1, 2.5),
            ]
        );

        $group = $this->createGroup();
        $service = $this->createCalculationService($group);
        $actual = $service->createGroupsFromQuery($query);
        self::assertCount(7, $actual);
        self::assertCount(7, $actual);
        self::assertArrayHasKey('groups', $actual);
        self::assertCount(6, $actual['groups']);
    }

    /**
     * @throws Exception|ORMException|\ReflectionException
     */
    public function testCreateGroupsFromQueryEmpty(): void
    {
        $query = new CalculationQuery(
            adjust: false,
            userMargin: 0.05,
            groups: []
        );

        $group = $this->createGroup();
        $service = $this->createCalculationService($group);
        $actual = $service->createGroupsFromQuery($query);
        self::assertCount(7, $actual);
        self::assertCount(7, $actual);
        self::assertArrayHasKey('groups', $actual);
        self::assertCount(1, $actual['groups']);
    }

    /**
     * @throws Exception|ORMException
     */
    public function testCreateGroupsFromQueryGroupNotFound(): void
    {
        $query = new CalculationQuery(
            adjust: false,
            userMargin: 0.05,
            groups: [
                new CalculationGroupQuery(10, 10.0),
            ]
        );

        $service = $this->createCalculationService();
        $actual = $service->createGroupsFromQuery($query);
        self::assertCount(7, $actual);
        self::assertCount(7, $actual);
        self::assertArrayHasKey('groups', $actual);
        self::assertCount(1, $actual['groups']);
    }

    /**
     * @throws Exception|ORMException|\ReflectionException
     */
    public function testCreateGroupsFromQueryGroupTotalZero(): void
    {
        $query = new CalculationQuery(
            adjust: false,
            userMargin: 0.05,
            groups: [
                new CalculationGroupQuery(1, 0.0),
            ]
        );

        $group = $this->createGroup();
        $service = $this->createCalculationService($group);
        $actual = $service->createGroupsFromQuery($query);
        self::assertCount(7, $actual);
        self::assertCount(7, $actual);
        self::assertArrayHasKey('groups', $actual);
        self::assertCount(1, $actual['groups']);
    }

    public function testGetConstants(): void
    {
        $constants = CalculationService::constants();
        self::assertCount(7, $constants);

        self::assertArrayHasKey('ROW_EMPTY', $constants);
        self::assertArrayHasKey('ROW_GLOBAL_MARGIN', $constants);
        self::assertArrayHasKey('ROW_GROUP', $constants);
        self::assertArrayHasKey('ROW_OVERALL_TOTAL', $constants);
        self::assertArrayHasKey('ROW_TOTAL_GROUP', $constants);
        self::assertArrayHasKey('ROW_TOTAL_NET', $constants);
        self::assertArrayHasKey('ROW_USER_MARGIN', $constants);

        self::assertSame(0, $constants['ROW_EMPTY']);
        self::assertSame(3, $constants['ROW_GLOBAL_MARGIN']);
        self::assertSame(1, $constants['ROW_GROUP']);
        self::assertSame(6, $constants['ROW_OVERALL_TOTAL']);
        self::assertSame(2, $constants['ROW_TOTAL_GROUP']);
        self::assertSame(4, $constants['ROW_TOTAL_NET']);
        self::assertSame(5, $constants['ROW_USER_MARGIN']);
    }

    /**
     * @throws Exception
     */
    public function testGetMinMargin(): void
    {
        $service = $this->createCalculationService();
        self::assertSame(1.1, $service->getMinMargin());
    }

    /**
     * @throws ORMException|Exception
     */
    public function testService(): void
    {
        self::bootKernel();

        $product = $this->init();
        $calculation = new Calculation();
        $calculation->addProduct($product, self::QUANTITY)
            ->setUserMargin(self::MARGIN_USER);

        $service = $this->createCalculationService();
        $service->updateTotal($calculation);

        self::assertSame(1, $calculation->getGroupsCount());
        self::assertSame(1, $calculation->getCategoriesCount());

        /** @var CalculationGroup $group */
        $group = $calculation->getGroups()->first();

        self::assertCount(1, $calculation->getGroups());
        self::assertCount(1, $group->getCategories());

        /** @var CalculationCategory $category */
        $category = $group->getCategories()->first();
        self::assertCount(1, $category->getItems());

        /** @var CalculationItem $item */
        $item = $category->getItems()->first();

        $totalItem = self::PRODUCT_PRICE * self::QUANTITY;
        $totalGroup = $totalItem * self::MARGIN_PERCENT;
        $totalUser = $totalGroup * (1.0 + self::MARGIN_USER);
        $totalOverall = $totalUser * self::MARGIN_PERCENT;

        // item
        self::assertSame(self::PRODUCT_PRICE, $item->getPrice());
        self::assertSame(self::QUANTITY, $item->getQuantity());
        self::assertSame($totalItem, $item->getTotal());

        // group
        self::assertSame($totalItem, $group->getAmount());
        self::assertSame(self::MARGIN_PERCENT, $group->getMargin());
        self::assertSame($totalGroup, $group->getTotal());

        // category
        self::assertSame($totalItem, $category->getAmount());
        self::assertSame($category->getAmount(), $item->getTotal());

        // assert
        self::assertSame($totalItem, $calculation->getItemsTotal());
        self::assertSame($totalGroup, $calculation->getGroupsTotal());
        self::assertSame(self::MARGIN_PERCENT, $calculation->getGlobalMargin());
        self::assertSame(self::MARGIN_USER, $calculation->getUserMargin());
        self::assertSame($totalOverall, $calculation->getOverallTotal());
    }

    /**
     * @throws ORMException|Exception
     */
    public function testUpdateTotalEmpty(): void
    {
        $calculation = new Calculation();
        $service = $this->createCalculationService();
        $actual = $service->updateTotal($calculation);
        self::assertFalse($actual);
    }

    /**
     * @throws Exception
     */
    protected function createCalculationService(
        ?Group $group = null,
        ?float $groupMargin = null,
        ?float $globalMargin = null,
    ): CalculationService {
        $globalMarginRepository = $this->createGlobalMarginRepository($globalMargin);
        $groupMarginRepository = $this->createGroupMarginRepository($groupMargin);
        $groupRepository = $this->createGroupRepository($group);
        $application = $this->getService(ApplicationService::class);
        $translator = $this->createMockTranslator();

        return new CalculationService(
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

    /**
     * @throws ORMException
     */
    protected function init(): Product
    {
        $manager = $this->getManager();
        $this->initGlobalMargins($manager);
        $category = $this->initCategories($manager);

        return $this->initProducts($manager, $category);
    }

    /**
     * @throws ORMException
     */
    protected function initCategories(EntityManager $manager): Category
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

    /**
     * @throws ORMException
     */
    protected function initGlobalMargins(EntityManager $manager): void
    {
        $this->initRepository($manager, GlobalMargin::class);

        $margin = new GlobalMargin();
        $margin->setMinimum(0)
            ->setMaximum(1_000_000)
            ->setMargin(self::MARGIN_PERCENT);
        $manager->persist($margin);

        $manager->flush();
    }

    /**
     * @throws ORMException
     */
    protected function initProducts(EntityManager $manager, Category $category): Product
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
     * @psalm-template TEntity of EntityInterface
     *
     * @psalm-param class-string<TEntity> $entityName
     *
     * @psalm-return EntityRepository<TEntity> $repository
     *
     * @throws ORMException
     */
    protected function initRepository(EntityManager $manager, string $entityName): EntityRepository
    {
        /** @psalm-var \App\Repository\AbstractRepository<TEntity> $repository */
        $repository = $manager->getRepository($entityName);

        $items = $repository->findAll();
        foreach ($items as $item) {
            $manager->remove($item);
        }
        $manager->flush();

        return $repository;
    }

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
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
