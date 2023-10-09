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

use App\Controller\ChartController;
use App\Tests\EntityTrait\CalculationStateTrait;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\CategoryTrait;
use App\Tests\EntityTrait\GroupTrait;
use App\Tests\EntityTrait\ProductTrait;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(ChartController::class)]
class ChartControllerTest extends AbstractControllerTestCase
{
    use CalculationStateTrait;
    use CalculationTrait;
    use CategoryTrait;
    use GroupTrait;
    use ProductTrait;

    public static function getRoutes(): \Generator
    {
        $routes = [
            '/chart/month',
            '/chart/month/pdf',
            '/chart/state',
            '/chart/state/pdf',
        ];
        $users = [
            self::ROLE_USER,
            self::ROLE_ADMIN,
            self::ROLE_SUPER_ADMIN,
        ];
        foreach ($routes as $route) {
            foreach ($users as $user) {
                yield [$route, $user];
            }
        }
        foreach ($routes as $route) {
            yield [$route, self::ROLE_DISABLED, Response::HTTP_FORBIDDEN];
        }
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
        $calculation->addProduct($product, 12.5);
        $this->updateCalculation();
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
