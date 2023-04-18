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

use App\Controller\CalculationBelowController;
use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Group;
use App\Entity\Product;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(CalculationBelowController::class)]
class CalculationBelowControllerTest extends AbstractTestController
{
    private static ?Calculation $calculation = null;
    private static ?Category $category = null;
    private static ?Group $group = null;
    private static ?Product $product = null;
    private static ?CalculationState $state = null;

    public static function getRoutes(): array
    {
        return [
            ['/below', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/below', self::ROLE_ADMIN],
            ['/below', self::ROLE_SUPER_ADMIN],

            ['/below/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/below/pdf', self::ROLE_ADMIN],
            ['/below/pdf', self::ROLE_SUPER_ADMIN],

            ['/below/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/below/excel', self::ROLE_ADMIN],
            ['/below/excel', self::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function addEntities(): void
    {
        if (!self::$state instanceof CalculationState) {
            self::$state = new CalculationState();
            self::$state->setCode('Test State');
            $this->addEntity(self::$state);
        }
        if (!self::$group instanceof Group) {
            self::$group = new Group();
            self::$group->setCode('Test Group');
            $this->addEntity(self::$group);
        }
        if (!self::$category instanceof Category) {
            self::$category = new Category();
            self::$category->setCode('Test Category')
                ->setGroup(self::$group);
            $this->addEntity(self::$category);
        }
        if (!self::$product instanceof Product) {
            self::$product = new Product();
            self::$product->setDescription('Test Product')
                ->setPrice(1.0)
                ->setCategory(self::$category);
            $this->addEntity(self::$product);
        }
        if (!self::$calculation instanceof Calculation) {
            self::$calculation = new Calculation();
            self::$calculation->setCustomer('Test Customer')
                ->setDescription('Test Description')
                ->setState(self::$state)
                ->addProduct(self::$product);

            self::$calculation->setItemsTotal(1.0)
                ->setGlobalMargin(1.0)
                ->setOverallTotal(2.0);
            $this->addEntity(self::$calculation);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteEntities(): void
    {
        self::$calculation = $this->deleteEntity(self::$calculation);
        self::$category = $this->deleteEntity(self::$category);
        self::$group = $this->deleteEntity(self::$group);
        self::$product = $this->deleteEntity(self::$product);
        self::$state = $this->deleteEntity(self::$state);
    }
}
