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

use App\Controller\CalculationEmptyController;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\ProductTrait;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(CalculationEmptyController::class)]
class CalculationEmptyControllerTest extends AbstractControllerTestCase
{
    use CalculationTrait;
    use ProductTrait;

    public static function getRoutes(): array
    {
        return [
            ['/empty', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/empty', self::ROLE_ADMIN],
            ['/empty', self::ROLE_SUPER_ADMIN],

            ['/empty/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/empty/pdf', self::ROLE_ADMIN],
            ['/empty/pdf', self::ROLE_SUPER_ADMIN],

            ['/empty/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/empty/excel', self::ROLE_ADMIN],
            ['/empty/excel', self::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $state = $this->getCalculationState();

        $product = $this->getProduct($category)
            ->setPrice(0.0);
        $this->addEntity($product);

        $calculation = $this->getCalculation($state)
            ->addProduct($product);
        $this->addEntity($calculation);
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteCalculation();
        $this->deleteProduct();
    }
}
