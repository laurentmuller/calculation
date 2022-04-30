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

namespace App\Tests\Controller;

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Group;
use App\Entity\GroupMargin;
use App\Entity\Product;

/**
 * Unit test for {@link PivotController} class.
 */
class PivotControllerTest extends AbstractControllerTest
{
    private static ?Calculation $calculation = null;
    private static ?Category $category = null;
    private static ?Group $group = null;
    private static ?Product $product = null;
    private static ?CalculationState $state = null;

    public function getRoutes(): array
    {
        return [
//             ['/pivot', self::ROLE_USER],
//             ['/pivot', self::ROLE_ADMIN],
//             ['/pivot', self::ROLE_SUPER_ADMIN],

            ['/pivot/csv', self::ROLE_USER],
            ['/pivot/csv', self::ROLE_ADMIN],
            ['/pivot/csv', self::ROLE_SUPER_ADMIN],

//             ['/pivot/json', self::ROLE_USER],
//             ['/pivot/json', self::ROLE_ADMIN],
//             ['/pivot/json', self::ROLE_SUPER_ADMIN],
        ];
    }

    protected function addEntities(): void
    {
        if (null === self::$state) {
            self::$state = new CalculationState();
            self::$state->setCode('Test State');
            $this->addEntity(self::$state);
        }

        if (null === self::$group) {
            $margin = new GroupMargin();
            $margin->setValues(0, 1000, 0.1);
            self::$group = new Group();
            self::$group->setCode('Test Group');
            self::$group->addMargin($margin);
            $this->addEntity(self::$group);
        }

        if (null === self::$category) {
            self::$category = new Category();
            self::$category->setCode('Test Category')
                ->setGroup(self::$group);
            $this->addEntity(self::$category);
        }

        if (null === self::$product) {
            self::$product = new Product();
            self::$product->setDescription('Test Product')
                ->setPrice(10.0)
                ->setCategory(self::$category);
            $this->addEntity(self::$product);
        }

        if (null === self::$calculation) {
            self::$calculation = new Calculation();
            self::$calculation->setCustomer('Test Customer')
                ->setDescription('Test Description')
                ->setState(self::$state)
                ->addProduct(self::$product, 10.0);
            $this->addEntity(self::$calculation);
        }
    }

    protected function deleteEntities(): void
    {
        self::$calculation = $this->deleteEntity(self::$calculation);
        self::$product = $this->deleteEntity(self::$product);
        self::$category = $this->deleteEntity(self::$category);
        self::$group = $this->deleteEntity(self::$group);
        self::$state = $this->deleteEntity(self::$state);
    }
}
