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
use App\Tests\EntityTrait\CalculationStateTrait;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\CategoryTrait;
use App\Tests\EntityTrait\GroupTrait;
use App\Tests\EntityTrait\ProductTrait;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(CalculationDuplicateController::class)]
class CalculationDuplicateControllerTest extends AbstractControllerTestCase
{
    use CalculationStateTrait;
    use CalculationTrait;
    use CategoryTrait;
    use GroupTrait;
    use ProductTrait;

    public static function getRoutes(): array
    {
        return [
            ['/duplicate', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/duplicate', self::ROLE_ADMIN],
            ['/duplicate', self::ROLE_SUPER_ADMIN],

            ['/duplicate/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/duplicate/pdf', self::ROLE_ADMIN],
            ['/duplicate/pdf', self::ROLE_SUPER_ADMIN],

            ['/duplicate/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/duplicate/excel', self::ROLE_ADMIN],
            ['/duplicate/excel', self::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $product = $this->getProduct($category);
        $state = $this->getCalculationState();

        $calculation = $this->getCalculation($state);
        $calculation->addProduct($product)
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
        $this->deleteCategory();
        $this->deleteGroup();
        $this->deleteCalculationState();
    }
}
