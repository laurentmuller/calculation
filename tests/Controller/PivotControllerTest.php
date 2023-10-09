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

use App\Controller\PivotController;
use App\Entity\GroupMargin;
use App\Tests\EntityTrait\CalculationStateTrait;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\CategoryTrait;
use App\Tests\EntityTrait\GroupTrait;
use App\Tests\EntityTrait\ProductTrait;

#[\PHPUnit\Framework\Attributes\CoversClass(PivotController::class)]
class PivotControllerTest extends AbstractControllerTestCase
{
    use CalculationStateTrait;
    use CalculationTrait;
    use CategoryTrait;
    use GroupTrait;
    use ProductTrait;

    public static function getRoutes(): array
    {
        return [
            ['/pivot/csv', self::ROLE_USER],
            ['/pivot/csv', self::ROLE_ADMIN],
            ['/pivot/csv', self::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
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

        $state = $this->getCalculationState();
        $calculation = $this->getCalculation($state)
            ->addProduct($product, 10.0);
        $this->addEntity($calculation);
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteCalculation();
        $this->deleteProduct();
        $this->deleteCategory();
        $this->deleteGroup();
        $this->deleteCalculationState();
    }
}
