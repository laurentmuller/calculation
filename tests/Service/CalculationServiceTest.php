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
use App\Repository\GlobalMarginRepository;
use App\Repository\GroupMarginRepository;
use App\Repository\GroupRepository;
use App\Service\ApplicationService;
use App\Service\CalculationService;
use App\Tests\DatabaseTrait;
use App\Tests\ServiceTrait;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Unit test for {@link CalculationService} class.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CalculationServiceTest extends KernelTestCase
{
    use DatabaseTrait;
    use ServiceTrait;

    private const MARGIN_PERCENT = 1.1;
    private const MARGIN_USER = 0.1;
    private const PRODUCT_PRICE = 100.0;
    private const QUANTITY = 10.0;

    public function testService(): void
    {
        self::bootKernel();

        $product = $this->init();
        $calculation = new Calculation();
        $calculation->addProduct($product, self::QUANTITY)
            ->setUserMargin(self::MARGIN_USER);

        $manager = $this->getManager();
        $service = $this->getTestedService($manager);
        $service->updateTotal($calculation);

        self::assertEquals(1, $calculation->getGroupsCount());
        self::assertEquals(1, $calculation->getCategoriesCount());

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
        $totalUser = $totalGroup * (1 + self::MARGIN_USER);
        $totalOverall = $totalUser * self::MARGIN_PERCENT;

        // item
        self::assertEquals(self::PRODUCT_PRICE, $item->getPrice());
        self::assertEquals(self::QUANTITY, $item->getQuantity());
        self::assertEquals($totalItem, $item->getTotal());

        // group
        self::assertEquals($totalItem, $group->getAmount());
        self::assertEquals(self::MARGIN_PERCENT, $group->getMargin());
        self::assertEquals($totalGroup, $group->getTotal());

        // category
        self::assertEquals($totalItem, $category->getAmount());
        self::assertEquals($category->getAmount(), $item->getTotal());

        // assert
        self::assertEquals($totalItem, $calculation->getItemsTotal());
        self::assertEquals($totalGroup, $calculation->getGroupsTotal());
        self::assertEquals(self::MARGIN_PERCENT, $calculation->getGlobalMargin());
        self::assertEquals(self::MARGIN_USER, $calculation->getUserMargin());
        self::assertEquals($totalOverall, $calculation->getOverallTotal());
    }

    /**
     * @param mixed $value
     */
    protected function echo(string $name, $value): void
    {
        echo \sprintf("\n%-15s: %s", $name, $value);
    }

    protected function getTestedService(EntityManager $manager): CalculationService
    {
        // get services
        $globalRepository = $this->getService(GlobalMarginRepository::class);
        $marginRepository = $this->getService(GroupMarginRepository::class);
        $groupRepository = $this->getService(GroupRepository::class);
        $service = $this->getService(ApplicationService::class);

        return new CalculationService($globalRepository, $marginRepository, $groupRepository, $service);
    }

    protected function init(): Product
    {
        $manager = $this->getManager();
        $this->initGlobalMargins($manager);
        $category = $this->initCategories($manager);

        return $this->initProducts($manager, $category);
    }

    protected function initCategories(EntityManager $manager): Category
    {
        $this->initRepository($manager, GroupMargin::class);
        $this->initRepository($manager, Category::class);
        $this->initRepository($manager, Group::class);

        $group = new Group();
        $group->setCode('Test');

        $margin = new GroupMargin();
        $margin->setValues(0, 1000000, self::MARGIN_PERCENT);
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

    protected function initGlobalMargins(EntityManager $manager): void
    {
        $this->initRepository($manager, GlobalMargin::class);

        $margin = new GlobalMargin();
        $margin->setValues(0, 1000000, self::MARGIN_PERCENT);
        $manager->persist($margin);

        $manager->flush();
    }

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
     * @psalm-template T of \App\Entity\AbstractEntity
     * @psalm-param class-string<T> $entityName
     * @psalm-return EntityRepository<T> $repository
     */
    protected function initRepository(EntityManager $manager, string $entityName): EntityRepository
    {
        /** @psalm-var \App\Repository\AbstractRepository<T> $repository */
        $repository = $manager->getRepository($entityName);

        $items = $repository->findAll();
        foreach ($items as $item) {
            $manager->remove($item);
        }
        $manager->flush();

        return $repository;
    }
}
