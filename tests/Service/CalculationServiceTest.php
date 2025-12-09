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
use App\Model\CalculationQueryGroup;
use App\Model\CalculationResultGroup;
use App\Parameter\ApplicationParameters;
use App\Repository\AbstractRepository;
use App\Repository\GlobalMarginRepository;
use App\Repository\GroupMarginRepository;
use App\Repository\GroupRepository;
use App\Service\CalculationService;
use App\Tests\DatabaseTrait;
use App\Tests\Entity\IdTrait;
use App\Tests\KernelServiceTestCase;
use App\Tests\TranslatorMockTrait;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;

final class CalculationServiceTest extends KernelServiceTestCase
{
    use DatabaseTrait;
    use IdTrait;
    use TranslatorMockTrait;

    private const MARGIN_PERCENT = 1.1;
    private const MARGIN_USER = 0.1;
    private const PRODUCT_PRICE = 100.0;
    private const QUANTITY = 10.0;

    public function testAdjustUserMargin(): void
    {
        $query = new CalculationAdjustQuery(
            adjust: true,
            userMargin: -0.99,
            groups: $this->createQueryGroups(id: 1, total: 100.0)
        );

        $groupMargin = new GroupMargin();
        $groupMargin->setMaximum(1000.0)
            ->setMargin(0.02);
        $group = $this->createGroup();
        $group->addMargin($groupMargin);
        $service = $this->createCalculationService($group, 1.0, 1.1);
        $actual = $service->createParameters($query);
        self::assertTrue($actual->result);
    }

    public function testCreateGroupsFromCalculation(): void
    {
        self::bootKernel();

        $product = $this->init();
        $calculation = new Calculation();

        $service = $this->createCalculationService();
        $groups = $service->createGroups($calculation);
        self::assertCount(1, $groups);

        $group = $groups->first();
        self::assertInstanceOf(CalculationResultGroup::class, $group);
        self::assertSame(-1, $group->id);
        self::assertSame(0.0, $group->amount);
        self::assertSame(0.0, $group->marginPercent);
        self::assertSame(0.0, $group->marginAmount);
        self::assertSame(0.0, $group->total);

        $calculation = new Calculation();
        $calculation->addProduct($product, self::QUANTITY);
        $groups = $service->createGroups($calculation);
        self::assertCount(6, $groups);

        $calculation = new Calculation();
        $calculation->addProduct($product, self::QUANTITY)
            ->setUserMargin(self::MARGIN_USER);
        $groups = $service->createGroups($calculation);
        self::assertCount(6, $groups);
    }

    public function testCreateGroupsFromDataEmpty(): void
    {
        $query = new CalculationAdjustQuery();
        $service = $this->createCalculationService();
        $actual = $service->createParameters($query);
        self::assertTrue($actual->result);
        self::assertCount(1, $actual->groups);
    }

    public function testCreateGroupsFromQuery(): void
    {
        $query = new CalculationAdjustQuery(
            adjust: false,
            userMargin: 0.05,
            groups: $this->createQueryGroups(id: 1, total: 2.5)
        );

        $group = $this->createGroup();
        $service = $this->createCalculationService($group);
        $actual = $service->createParameters($query);
        self::assertTrue($actual->result);
        self::assertCount(6, $actual->groups);
    }

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
        self::assertTrue($actual->result);
        self::assertCount(1, $actual->groups);
    }

    public function testCreateGroupsFromQueryGroupNotFound(): void
    {
        $query = new CalculationAdjustQuery(
            adjust: false,
            userMargin: 0.05,
            groups: $this->createQueryGroups(id: 10, total: 10.0)
        );

        $service = $this->createCalculationService();
        $actual = $service->createParameters($query);
        self::assertTrue($actual->result);
        self::assertCount(1, $actual->groups);
    }

    public function testCreateGroupsFromQueryGroupTotalZero(): void
    {
        $query = new CalculationAdjustQuery(
            adjust: false,
            userMargin: 0.05,
            groups: $this->createQueryGroups(id: 1, total: 0.0)
        );

        $group = $this->createGroup();
        $service = $this->createCalculationService($group);
        $actual = $service->createParameters($query);
        self::assertTrue($actual->result);
        self::assertCount(1, $actual->groups);
    }

    public function testGetConstants(): void
    {
        $constants = CalculationService::constants();
        self::assertCount(7, $constants);

        $this->assertValidConstant($constants, 'ROW_EMPTY', -1);
        $this->assertValidConstant($constants, 'ROW_GROUP', -2);
        $this->assertValidConstant($constants, 'ROW_TOTAL_GROUP', -3);
        $this->assertValidConstant($constants, 'ROW_GLOBAL_MARGIN', -4);
        $this->assertValidConstant($constants, 'ROW_TOTAL_NET', -5);
        $this->assertValidConstant($constants, 'ROW_USER_MARGIN', -6);
        $this->assertValidConstant($constants, 'ROW_OVERALL_TOTAL', -7);
    }

    private function assertValidConstant(array $constants, string $name, int $expected): void
    {
        self::assertArrayHasKey($name, $constants);
        self::assertSame($expected, $constants[$name]);
    }

    private function createApplicationParameters(): ApplicationParameters
    {
        $parameters = $this->createMock(ApplicationParameters::class);
        $parameters->method('isMarginBelow')
            ->willReturn(true);

        return $parameters;
    }

    private function createCalculationService(
        ?Group $group = null,
        ?float $groupMargin = null,
        ?float $globalMargin = null
    ): CalculationService {
        $globalMarginRepository = $this->createGlobalMarginRepository($globalMargin);
        $groupMarginRepository = $this->createGroupMarginRepository($groupMargin);
        $groupRepository = $this->createGroupRepository($group);
        $parameters = $this->createApplicationParameters();
        $translator = $this->createMockTranslator();

        return new CalculationService(
            $globalMarginRepository,
            $groupMarginRepository,
            $groupRepository,
            $parameters,
            $translator
        );
    }

    private function createGlobalMarginRepository(?float $globalMargin = null): GlobalMarginRepository
    {
        $repository = $this->createMock(GlobalMarginRepository::class);
        $repository->method('getMargin')
            ->willReturn($globalMargin ?? 0.0);

        return $repository;
    }

    private function createGroup(): Group
    {
        $group = new Group();
        $group->setCode('Fake Code');

        return self::setId($group);
    }

    private function createGroupMarginRepository(?float $groupMargin = null): GroupMarginRepository
    {
        $repository = $this->createMock(GroupMarginRepository::class);
        $repository->method('getGroupMargin')
            ->willReturn($groupMargin ?? 0.0);

        return $repository;
    }

    private function createGroupRepository(?Group $group = null): GroupRepository
    {
        $repository = $this->createMock(GroupRepository::class);
        $repository->method('findGroupCode')
            ->willReturn($group?->getCode());

        return $repository;
    }

    /**
     * @return CalculationQueryGroup[]
     */
    private function createQueryGroups(int $id, float $total): array
    {
        return [new CalculationQueryGroup(id: $id, total: $total)];
    }

    private function init(): Product
    {
        $manager = $this->getManager();
        $this->initGlobalMargins($manager);
        $category = $this->initCategories($manager);

        return $this->initProducts($manager, $category);
    }

    private function initCategories(ObjectManager $manager): Category
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

    private function initGlobalMargins(ObjectManager $manager): void
    {
        $this->initRepository($manager, GlobalMargin::class);

        $margin = new GlobalMargin();
        $margin->setMinimum(0)
            ->setMaximum(1_000_000)
            ->setMargin(self::MARGIN_PERCENT);
        $manager->persist($margin);

        $manager->flush();
    }

    private function initProducts(ObjectManager $manager, Category $category): Product
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
    private function initRepository(ObjectManager $manager, string $entityName): EntityRepository
    {
        /** @phpstan-var AbstractRepository<TEntity> $repository */
        $repository = $manager->getRepository($entityName);

        $items = $repository->findAll();
        foreach ($items as $item) {
            $manager->remove($item);
        }
        $manager->flush();

        return $repository;
    }
}
