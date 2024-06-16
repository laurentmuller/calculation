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
use App\Report\Table\GroupsTable;
use App\Report\Table\ItemsTable;
use App\Report\Table\OverallTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(CalculationReport::class)]
#[CoversClass(ItemsTable::class)]
#[CoversClass(GroupsTable::class)]
#[CoversClass(OverallTable::class)]
class CalculationReportTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testEmpty(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $logger = $this->createMock(LoggerInterface::class);

        $calculation = new Calculation();
        $calculation->setDescription('description')
            ->setCustomer('customer')
            ->setOverallTotal(1000.0)
            ->setGlobalMargin(1.0)
            ->setItemsTotal(1000.0)
            ->setUserMargin(0.1);

        $report = new CalculationReport($controller, $calculation, 1.1, '', $logger);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testEmptyGroup(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $logger = $this->createMock(LoggerInterface::class);

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

        $report = new CalculationReport($controller, $calculation, 1.1, 'qrcode', $logger);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRender(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $logger = $this->createMock(LoggerInterface::class);

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

        $report = new CalculationReport($controller, $calculation, 2.0, 'qrcode', $logger);
        self::assertInstanceOf(LoggerInterface::class, $report->getLogger());
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRenderWithoutQrCode(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $logger = $this->createMock(LoggerInterface::class);

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

        $report = new CalculationReport($controller, $calculation, 2.0, '', $logger);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
