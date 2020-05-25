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
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Test for the calculation service.
 *
 * @author Laurent Muller
 */
class CalculationServiceTest extends WebTestCase
{
    const MARGIN_PERCENT = 0.1;
    const PRODUCT_PRICE = 100.0;
    const QUANTITY = 10.0;

    public function testService(): void
    {
        self::bootKernel();

        $product = $this->initDatabase();
        $calculation = new Calculation();
        $calculation->addProduct($product, self::QUANTITY);

        $manager = $this->getManager();
        $service = $this->getService($manager);
        $service->updateTotal($calculation);

        $this->assertCount(1, $calculation->getGroups());

        /** @var CalculationGroup $group */
        $group = $calculation->getGroups()[0];
        $total = self::PRODUCT_PRICE * self::QUANTITY * (1 + self::MARGIN_PERCENT);
        $this->assertSame(self::PRODUCT_PRICE * self::QUANTITY, $group->getAmount());
        $this->assertSame(self::MARGIN_PERCENT, $group->getMargin());
        $this->assertSame($total, $group->getTotal());

        $this->assertCount(1, $group->getItems());

        /** @var CalculationItem $item */
        $item = $group->getItems()[0];
        $this->assertSame(self::PRODUCT_PRICE, $item->getPrice());
        $this->assertSame(self::QUANTITY, $item->getQuantity());
        $this->assertSame(self::PRODUCT_PRICE * self::QUANTITY, $item->getTotal());

        $total *= (1 + self::MARGIN_PERCENT);
        $this->assertSame(self::PRODUCT_PRICE * self::QUANTITY, $calculation->getItemsTotal());
        $this->assertSame(self::MARGIN_PERCENT, $calculation->getGlobalMargin());
        $this->assertSame($total, $calculation->getOverallTotal());
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

    protected function initCategories(EntityManager $manager): Category
    {
        $this->initRepository($manager, Category::class);

        $category = new Category();
        $category->setCode('Test')
            ->setDescription('Test description');

        $margin = new CategoryMargin();
        $margin->setValues(0, 1000000, self::MARGIN_PERCENT);
        $category->addMargin($margin);

        $manager->persist($category);
        $manager->flush();

        return $category;
    }

    protected function initDatabase(): Product
    {
        $manager = $this->getManager();
        $this->initGlobalMargins($manager);
        $category = $this->initCategories($manager);
        $product = $this->initProducts($manager, $category);

        return $product;
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

        return $repository;
    }
}
