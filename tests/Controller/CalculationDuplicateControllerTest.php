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

use App\Controller\CalculationDuplicateController;
use App\Entity\Product;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\ProductTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(CalculationDuplicateController::class)]
class CalculationDuplicateControllerTest extends AbstractControllerTestCase
{
    use CalculationTrait;
    use ProductTrait;

    private ?Product $duplicate = null;

    public static function getRoutes(): \Iterator
    {
        yield ['/calculation/duplicate', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/calculation/duplicate', self::ROLE_ADMIN];
        yield ['/calculation/duplicate', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/duplicate/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/calculation/duplicate/pdf', self::ROLE_ADMIN];
        yield ['/calculation/duplicate/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/duplicate/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/calculation/duplicate/excel', self::ROLE_ADMIN];
        yield ['/calculation/duplicate/excel', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        $product = $this->getProduct();
        if (!$this->duplicate instanceof Product) {
            $this->duplicate = clone $product;
            $this->addEntity($this->duplicate);
        }

        $calculation = $this->getCalculation();
        $calculation->addProduct($this->duplicate)
            ->addProduct($product);
        $this->addEntity($calculation);
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        $this->duplicate = $this->deleteEntity($this->duplicate);
        $this->deleteCalculation();
        $this->deleteProduct();
    }
}
