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

use App\Controller\AbstractController;
use App\Controller\AbstractEntityController;
use App\Controller\CalculationController;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\ProductTrait;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractController::class)]
#[CoversClass(AbstractEntityController::class)]
#[CoversClass(CalculationController::class)]
class CalculationControllerTest extends AbstractControllerTestCase
{
    use CalculationTrait;
    use ProductTrait;

    public static function getRoutes(): \Iterator
    {
        yield ['/calculation', self::ROLE_USER];
        yield ['/calculation', self::ROLE_ADMIN];
        yield ['/calculation', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/add', self::ROLE_USER];
        yield ['/calculation/add', self::ROLE_ADMIN];
        yield ['/calculation/add', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/edit/1', self::ROLE_USER];
        yield ['/calculation/edit/1', self::ROLE_ADMIN];
        yield ['/calculation/edit/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/state/1', self::ROLE_USER];
        yield ['/calculation/state/1', self::ROLE_ADMIN];
        yield ['/calculation/state/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/delete/1', self::ROLE_USER];
        yield ['/calculation/delete/1', self::ROLE_ADMIN];
        yield ['/calculation/delete/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/clone/1', self::ROLE_USER];
        yield ['/calculation/clone/1', self::ROLE_ADMIN];
        yield ['/calculation/clone/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/show/1', self::ROLE_USER];
        yield ['/calculation/show/1', self::ROLE_ADMIN];
        yield ['/calculation/show/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/pdf/1', self::ROLE_USER];
        yield ['/calculation/pdf/1', self::ROLE_ADMIN];
        yield ['/calculation/pdf/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/pdf', self::ROLE_USER];
        yield ['/calculation/pdf', self::ROLE_ADMIN];
        yield ['/calculation/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/excel', self::ROLE_USER];
        yield ['/calculation/excel', self::ROLE_ADMIN];
        yield ['/calculation/excel', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/excel/1', self::ROLE_USER];
        yield ['/calculation/excel/1', self::ROLE_ADMIN];
        yield ['/calculation/excel/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculation?search=22', self::ROLE_USER];
        yield ['/calculation?search=22', self::ROLE_ADMIN];
        yield ['/calculation?search=22', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        $product = $this->getProduct();
        $this->getCalculation()
            ->setOverallTotal(100.0)
            ->setItemsTotal(100.0)
            ->setGlobalMargin(1.1)
            ->setUserMargin(0.1)
            ->addProduct($product);
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteCalculation();
        $this->deleteCategory();
    }
}
