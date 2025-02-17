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

use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\ProductTrait;
use Symfony\Component\HttpFoundation\Response;

class TimelineControllerTest extends ControllerTestCase
{
    use CalculationTrait;
    use ProductTrait;

    #[\Override]
    public static function getRoutes(): \Generator
    {
        $routes = [
            '/timeline',
            '/timeline/content?date=2024-01-01&interval=P3D',
            '/timeline/today?date=2024-01-01&interval=P3D',
            '/timeline/first?interval=P3D',
            '/timeline/last?interval=P3D',
        ];

        foreach ($routes as $route) {
            yield [$route, self::ROLE_USER, Response::HTTP_FORBIDDEN];
            yield [$route, self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
            yield [$route, self::ROLE_SUPER_ADMIN];
        }
    }

    #[\Override]
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

    #[\Override]
    protected function deleteEntities(): void
    {
        $this->deleteCalculation();
        $this->deleteCategory();
    }
}
