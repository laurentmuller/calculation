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

use App\Entity\GroupMargin;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\ProductTrait;

class PivotControllerTest extends ControllerTestCase
{
    use CalculationTrait;
    use ProductTrait;

    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/pivot', self::ROLE_USER];
        yield ['/pivot', self::ROLE_ADMIN];
        yield ['/pivot', self::ROLE_SUPER_ADMIN];

        yield ['/pivot/csv', self::ROLE_USER];
        yield ['/pivot/csv', self::ROLE_ADMIN];
        yield ['/pivot/csv', self::ROLE_SUPER_ADMIN];

        yield ['/pivot/json', self::ROLE_USER];
        yield ['/pivot/json', self::ROLE_ADMIN];
        yield ['/pivot/json', self::ROLE_SUPER_ADMIN];
    }

    #[\Override]
    protected function addEntities(): void
    {
        $margin = new GroupMargin();
        $margin->setMinimum(0)
            ->setMaximum(1000)
            ->setMargin(0.1);
        $group = $this->getGroup()
            ->addMargin($margin);
        $this->addEntity($group);

        $category = $this->getCategory($group);
        $product = $this->getProduct($category)
            ->setPrice(10.0);
        $this->addEntity($product);

        $calculation = $this->getCalculation()
            ->addProduct($product, 10.0);
        $this->updateCalculation();
        $this->addEntity($calculation);
    }

    #[\Override]
    protected function deleteEntities(): void
    {
        $this->deleteCalculation();
        $this->deleteProduct();
    }
}
