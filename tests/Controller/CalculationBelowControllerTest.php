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
use App\Tests\EntityTrait\CalculationStateTrait;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\CategoryTrait;
use App\Tests\EntityTrait\GroupTrait;
use App\Tests\EntityTrait\ProductTrait;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(CalculationBelowController::class)]
class CalculationBelowControllerTest extends AbstractControllerTestCase
{
    use CalculationStateTrait;
    use CalculationTrait;
    use CategoryTrait;
    use GroupTrait;
    use ProductTrait;

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
     * @throws ORMException
     */
    protected function addEntities(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $product = $this->getProduct($category);
        $state = $this->getCalculationState();

        $calculation = $this->getCalculation($state)
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
        $this->deleteCategory();
        $this->deleteGroup();
        $this->deleteCalculationState();
    }
}
