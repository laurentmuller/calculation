<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Calculation;
use App\Entity\CalculationGroup;
use App\Entity\CalculationItem;
use App\Entity\Category;
use App\Entity\CategoryMargin;
use App\Entity\GlobalMargin;
use App\Entity\Product;
use App\Service\ApplicationService;
use App\Service\CalculationService;
use App\Tests\DatabaseTrait;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Test for the calculation service.
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

        $this->assertCount(2, $calculation->getGroups());
        $this->assertCount(1, $calculation->getGroups()[1]->getItems());

        /** @var CalculationGroup $rootGroup */
        $rootGroup = $calculation->getGroups()[0];

        /** @var CalculationGroup $itemsGroup */
        $itemsGroup = $calculation->getGroups()[1];

        /** @var CalculationItem $item */
        $item = $itemsGroup->getItems()[0];

        $totalItem = self::PRODUCT_PRICE * self::QUANTITY;
        $totalGroup = $totalItem * self::MARGIN_PERCENT;
        $totalUser = $totalGroup * (1 + self::MARGIN_USER);
        $totalOverall = $totalUser * self::MARGIN_PERCENT;

        // item
        $this->assertSame(self::PRODUCT_PRICE, $item->getPrice());
        $this->assertSame(self::QUANTITY, $item->getQuantity());
        $this->assertSame($totalItem, $item->getTotal());

        // parent group
        $this->assertSame($totalItem, $rootGroup->getAmount());
        $this->assertSame(self::MARGIN_PERCENT, $rootGroup->getMargin());
        $this->assertSame($totalGroup, $rootGroup->getTotal());

        // items group
        $this->assertSame($totalItem, $itemsGroup->getAmount());
        $this->assertSame(0.0, $itemsGroup->getTotal());
        $this->assertSame($itemsGroup->getAmount(), $item->getTotal());

        // calculation
        $this->assertSame($totalItem, $calculation->getItemsTotal());
        $this->assertSame($totalGroup, $calculation->getGroupsTotal());
        $this->assertSame(self::MARGIN_PERCENT, $calculation->getGlobalMargin());
        $this->assertSame(self::MARGIN_USER, $calculation->getUserMargin());
        $this->assertSame($totalOverall, $calculation->getOverallTotal());
    }

    protected function echo(string $name, $value): void
    {
        echo \sprintf("\n%-15s: %s", $name, $value);
    }

    protected function getManager(): EntityManager
    {
        /** @var ManagerRegistry $registry */
        $registry = self::$container->get('doctrine');

        return $registry->getManager();
    }

    protected function getService(EntityManager $manager): CalculationService
    {
        $service = self::$container->get(ApplicationService::class);
        $translator = self::$container->get(TranslatorInterface::class);
        $service = new CalculationService($manager, $service, $translator);

        return $service;
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
        $this->initRepository($manager, CategoryMargin::class);
        $this->initRepository($manager, Category::class);

        $parent = new Category();
        $parent->setCode('Test');

        $category = new Category();
        $category->setCode('Test')
            ->setParent($parent);

        $margin = new CategoryMargin();
        $margin->setValues(0, 1000000, self::MARGIN_PERCENT);
        $parent->addMargin($margin);

        $manager->persist($parent);
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
            ->setCategory($category)
            ->setPrice(self::PRODUCT_PRICE);

        $manager->persist($product);
        $manager->flush();

        return $product;
    }

    protected function initRepository(EntityManager $manager, string $entityName): EntityRepository
    {
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
