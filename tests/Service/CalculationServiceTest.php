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

#[\PHPUnit\Framework\Attributes\CoversClass(CalculationService::class)]
class CalculationServiceTest extends KernelTestCase
{
    use DatabaseTrait;
    use ServiceTrait;

    private const MARGIN_PERCENT = 1.1;
    private const MARGIN_USER = 0.1;
    private const PRODUCT_PRICE = 100.0;
    private const QUANTITY = 10.0;

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function testService(): void
    {
        self::bootKernel();

        $product = $this->init();
        $calculation = new Calculation();
        $calculation->addProduct($product, self::QUANTITY)
            ->setUserMargin(self::MARGIN_USER);

        $service = $this->getTestedService();
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

    protected function echo(string $name, mixed $value): void
    {
        if (\is_scalar($value) || (\is_object($value) && \method_exists($value, '__toString'))) {
            echo \sprintf("\n%-15s: %s", $name, (string) $value);
        }
    }

    protected function getTestedService(): CalculationService
    {
        // get services
        $globalRepository = $this->getService(GlobalMarginRepository::class);
        $marginRepository = $this->getService(GroupMarginRepository::class);
        $groupRepository = $this->getService(GroupRepository::class);
        $service = $this->getService(ApplicationService::class);

        return new CalculationService($globalRepository, $marginRepository, $groupRepository, $service);
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function init(): Product
    {
        $manager = $this->getManager();
        $this->initGlobalMargins($manager);
        $category = $this->initCategories($manager);

        return $this->initProducts($manager, $category);
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
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
     * @throws \Doctrine\ORM\Exception\ORMException
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
     * @throws \Doctrine\ORM\Exception\ORMException
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
     * @psalm-template T of \App\Entity\AbstractEntity
     *
     * @psalm-param class-string<T> $entityName
     *
     * @psalm-return EntityRepository<T> $repository
     *
     * @throws \Doctrine\ORM\Exception\ORMException
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
