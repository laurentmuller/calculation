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
use Symfony\Component\HttpFoundation\Response;

class CalculationBelowControllerTest extends ControllerTestCase
{
    use CalculationTrait;
    use ProductTrait;

    #[\Override]
    public static function getRoutes(): \Generator
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

    public function testEmptyCalculation(): void
    {
        $this->deleteEntitiesByClass(Calculation::class);
        $this->checkRoute('/calculation/below/pdf', self::ROLE_ADMIN, Response::HTTP_FOUND);
        $this->checkRoute('/calculation/below/excel', self::ROLE_ADMIN, Response::HTTP_FOUND);
    }

    #[\Override]
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

    #[\Override]
    protected function deleteEntities(): void
    {
        $this->deleteCalculation();
        $this->deleteProduct();
    }
}
