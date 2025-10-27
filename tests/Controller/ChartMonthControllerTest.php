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
use App\Tests\EntityTrait\GlobalMarginTrait;
use App\Tests\EntityTrait\ProductTrait;
use Symfony\Component\HttpFoundation\Response;

final class ChartMonthControllerTest extends ControllerTestCase
{
    use CalculationTrait;
    use GlobalMarginTrait;
    use ProductTrait;

    #[\Override]
    public static function getRoutes(): \Generator
    {
        $routes = [
            '/chart/month',
            '/chart/month/pdf',
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

    public function testIndexInvalidMonth(): void
    {
        $route = 'chart/month?count=0';
        $this->checkRoute($route, self::ROLE_USER, Response::HTTP_BAD_REQUEST);
    }

    public function testPdfInvalidMonth(): void
    {
        $route = 'chart/month/pdf?count=0';
        $this->checkRoute($route, self::ROLE_USER, Response::HTTP_BAD_REQUEST);
    }

    #[\Override]
    protected function addEntities(): void
    {
        $this->getGlobalMargin();
        $product = $this->getProduct();
        $calculation = $this->getCalculation();
        $calculation->addProduct($product, 12.5);
        $this->updateCalculation();
    }

    #[\Override]
    protected function deleteEntities(): void
    {
        $this->deleteGlobalMargin();
        $this->deleteCalculation();
        $this->deleteProduct();
    }

    #[\Override]
    protected function mustDeleteEntities(): bool
    {
        return true;
    }
}
