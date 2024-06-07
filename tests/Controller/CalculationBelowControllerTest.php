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
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\ProductTrait;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(CalculationBelowController::class)]
class CalculationBelowControllerTest extends AbstractControllerTestCase
{
    use CalculationTrait;
    use ProductTrait;

    public static function getRoutes(): \Iterator
    {
        yield ['/calculation/below', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/calculation/below', self::ROLE_ADMIN];
        yield ['/calculation/below', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/below/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/calculation/below/pdf', self::ROLE_ADMIN];
        yield ['/calculation/below/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/below/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/calculation/below/excel', self::ROLE_ADMIN];
        yield ['/calculation/below/excel', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws ORMException
     */
    protected function addEntities(): void
    {
        $product = $this->getProduct();
        $calculation = $this->getCalculation()
            ->addProduct($product)
            ->setItemsTotal(1.0)
            ->setGlobalMargin(1.0)
            ->setOverallTotal(2.0);
        $this->addEntity($calculation);
    }

    /**
     * @throws ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteCalculation();
        $this->deleteProduct();
    }
}
