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

use App\Model\MonthChartData;
use App\Repository\CalculationRepository;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\GlobalMarginTrait;
use App\Tests\EntityTrait\ProductTrait;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\HttpFoundation\Request;
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
            '/chart/month/period',
        ];
        foreach ($routes as $route) {
            foreach (self::DEFAULT_USERS as $user) {
                yield [$route, $user];
            }
        }
        foreach ($routes as $route) {
            yield [$route, self::ROLE_DISABLED, Response::HTTP_FORBIDDEN];
        }
    }

    public function testPeriodSubmit(): void
    {
        $date = new DatePoint();
        $data = new MonthChartData([]);
        $repository = self::createStub(CalculationRepository::class);
        $repository->method('getMinMaxDates')
            ->willReturn([$date, $date]);
        $repository->method('getMonthChartData')
            ->willReturn($data);
        $this->setService(CalculationRepository::class, $repository);

        $this->checkForm(
            uri: '/chart/month/period',
            method: Request::METHOD_GET,
            disableReboot: true,
        );
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
