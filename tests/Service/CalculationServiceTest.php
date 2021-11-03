<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
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
use App\Repository\AbstractRepository;
use App\Service\ApplicationService;
use App\Service\CalculationService;
use App\Tests\DatabaseTrait;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Unit test for {@link App\Service\CalculationService} class.
 *
 * @author Laurent Muller
 */
class CalculationServiceTest extends KernelTestCase
{
    use DatabaseTrait;

    public const MARGIN_PERCENT = 1.1;
    public const MARGIN_USER = 0.1;
    public const PRODUCT_PRICE = 100.0;
    public const QUANTITY = 10.0;

    public function testService(): void
    {
        self::bootKernel();

        $product = $this->init();
        $calculation = new Calculation();
        $calculation->addProduct($product, self::QUANTITY)
            ->setUserMargin(self::MARGIN_USER);

        $manager = $this->getManager();
        $service = $this->getService($manager);
        $service->updateTotal($calculation);

        $this->assertEquals(1, $calculation->getGroupsCount());
        $this->assertEquals(1, $calculation->getCategoriesCount());

        /** @var CalculationGroup $group */
        $group = $calculation->getGroups()->first();

        $this->assertCount(1, $calculation->getGroups());
        $this->assertCount(1, $group->getCategories());

        /** @var CalculationCategory $category */
        $category = $group->getCategories()->first();

        /** @var CalculationItem $item */
        $item = $category->getItems()->first();

        $totalItem = self::PRODUCT_PRICE * self::QUANTITY;
        $totalGroup = $totalItem * self::MARGIN_PERCENT;
        $totalUser = $totalGroup * (1 + self::MARGIN_USER);
        $totalOverall = $totalUser * self::MARGIN_PERCENT;

        // item
        $this->assertEquals(self::PRODUCT_PRICE, $item->getPrice());
        $this->assertEquals(self::QUANTITY, $item->getQuantity());
        $this->assertEquals($totalItem, $item->getTotal());

        // group
        $this->assertEquals($totalItem, $group->getAmount());
        $this->assertEquals(self::MARGIN_PERCENT, $group->getMargin());
        $this->assertEquals($totalGroup, $group->getTotal());

        // category
        $this->assertEquals($totalItem, $category->getAmount());
        $this->assertEquals($category->getAmount(), $item->getTotal());

        // assert
        $this->assertEquals($totalItem, $calculation->getItemsTotal());
        $this->assertEquals($totalGroup, $calculation->getGroupsTotal());
        $this->assertEquals(self::MARGIN_PERCENT, $calculation->getGlobalMargin());
        $this->assertEquals(self::MARGIN_USER, $calculation->getUserMargin());
        $this->assertEquals($totalOverall, $calculation->getOverallTotal());
    }

    /**
     * @param mixed $value
     */
    protected function echo(string $name, $value): void
    {
        echo \sprintf("\n%-15s: %s", $name, $value);
    }

    protected function getService(EntityManager $manager): CalculationService
    {
        /** @var ApplicationService $application */
        $application = static::getContainer()->get(ApplicationService::class);
        /** @var TranslatorInterface $translator */
        $translator = static::getContainer()->get(TranslatorInterface::class);

        return new CalculationService($manager, $application, $translator);
    }

    protected function init(): Product
    {
        $manager = $this->getManager();
        $this->initGlobalMargins($manager);
        $category = $this->initCategories($manager);
        $product = $this->initProducts($manager, $category);

        return $product;
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
     * @template T of \App\Entity\AbstractEntity
     *
     * @param class-string<T> $entityName
     * @psalm-return EntityRepository<T> $repository
     */
    protected function initRepository(EntityManager $manager, string $entityName): EntityRepository
    {
        /**
         *  @var AbstractRepository $repository
         *  @psalm-var AbstractRepository<T> $repository
         */
        $repository = $manager->getRepository($entityName);

        // remove existing elements
        $items = $repository->findAll();
        foreach ($items as $item) {
            $manager->remove($item);
        }
        $manager->flush();

        return $repository;
    }
}
