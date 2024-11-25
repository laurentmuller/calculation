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
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\ProductTrait;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\HttpFoundation\Response;

class CalculationEmptyControllerTest extends ControllerTestCase
{
    use CalculationTrait;
    use ProductTrait;

    public static function getRoutes(): \Iterator
    {
        yield ['/calculation/empty', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/calculation/empty', self::ROLE_ADMIN];
        yield ['/calculation/empty', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/empty/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/calculation/empty/pdf', self::ROLE_ADMIN];
        yield ['/calculation/empty/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/empty/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/calculation/empty/excel', self::ROLE_ADMIN];
        yield ['/calculation/empty/excel', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws ORMException
     */
    public function testEmptyCalculation(): void
    {
        $this->deleteEntitiesByClass(Calculation::class);
        $this->checkRoute('/calculation/empty/pdf', self::ROLE_ADMIN, Response::HTTP_FOUND);
        $this->checkRoute('/calculation/empty/excel', self::ROLE_ADMIN, Response::HTTP_FOUND);
    }

    /**
     * @throws ORMException
     */
    protected function addEntities(): void
    {
        $product = $this->getProduct()
            ->setPrice(0.0);
        $this->addEntity($product);
        $calculation = $this->getCalculation()
            ->addProduct($product);
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
