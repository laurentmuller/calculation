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

namespace App\Tests\Report;

use App\Controller\AbstractController;
use App\Entity\Calculation;
use App\Entity\CalculationCategory;
use App\Entity\CalculationGroup;
use App\Entity\CalculationItem;
use App\Report\CalculationReport;
use PHPUnit\Framework\TestCase;

class CalculationReportTest extends TestCase
{
    public function testEmpty(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('description')
            ->setCustomer('customer')
            ->setOverallTotal(1000.0)
            ->setGlobalMargin(1.0)
            ->setItemsTotal(1000.0)
            ->setUserMargin(0.1);
        $controller = $this->createMock(AbstractController::class);
        $report = new CalculationReport($controller, $calculation, 1.1, '');
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testEmptyGroup(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('description')
            ->setCustomer('customer')
            ->setOverallTotal(1000.0)
            ->setGlobalMargin(1.2)
            ->setItemsTotal(800.0)
            ->setUserMargin(0.1);

        $group = new CalculationGroup();
        $group->setCode('Group');
        $calculation->addGroup($group);

        $controller = $this->createMock(AbstractController::class);
        $report = new CalculationReport($controller, $calculation, 1.1, 'qrcode');
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRender(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('description')
            ->setCustomer('customer')
            ->setOverallTotal(1000.0)
            ->setGlobalMargin(1.2)
            ->setItemsTotal(800.0)
            ->setUserMargin(0.1);

        $group = new CalculationGroup();
        $group->setCode('Group');

        $category = new CalculationCategory();
        $category->setCode('Category');

        $item = new CalculationItem();
        $item->setDescription('Description')
            ->setPrice(100.0)
            ->setQuantity(0.0);
        $category->addItem($item);
        $group->addCategory($category);
        $calculation->addGroup($group);

        $controller = $this->createMock(AbstractController::class);
        $report = new CalculationReport($controller, $calculation, 2.0, 'qrcode');
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderWithoutQrCode(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('description')
            ->setCustomer('customer')
            ->setOverallTotal(1000.0)
            ->setGlobalMargin(1.2)
            ->setItemsTotal(800.0)
            ->setUserMargin(0.1);

        $group = new CalculationGroup();
        $group->setCode('Group');

        $category = new CalculationCategory();
        $category->setCode('Category');

        $item = new CalculationItem();
        $item->setDescription('Description')
            ->setPrice(100.0)
            ->setQuantity(0.0);
        $category->addItem($item);
        $group->addCategory($category);
        $calculation->addGroup($group);

        $controller = $this->createMock(AbstractController::class);
        $report = new CalculationReport($controller, $calculation, 2.0, '');
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
