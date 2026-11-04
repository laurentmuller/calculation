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

use App\Entity\Calculation;
use App\Entity\CalculationCategory;
use App\Entity\CalculationGroup;
use App\Entity\CalculationItem;
use App\Interfaces\DocumentHelperInterface;
use App\Report\CalculationReport;
use Endroid\QrCode\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

final class CalculationReportTest extends TestCase
{
    /**
     * @throws ValidationException
     */
    public function testEmpty(): void
    {
        $calculation = new Calculation();
        $calculation->withDescription('description')
            ->withCustomer('customer')
            ->withOverallTotal(1000.0)
            ->withGlobalMargin(1.0)
            ->withItemsTotal(1000.0)
            ->withUserMargin(0.1);
        $helper = self::createStub(DocumentHelperInterface::class);
        $report = new CalculationReport($helper, $calculation, 1.1, '');
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws ValidationException
     */
    public function testEmptyGroup(): void
    {
        $calculation = new Calculation();
        $calculation->withDescription('description')
            ->withCustomer('customer')
            ->withOverallTotal(1000.0)
            ->withGlobalMargin(1.2)
            ->withItemsTotal(800.0)
            ->withUserMargin(0.1);

        $group = new CalculationGroup();
        $group->withCode('Group');
        $calculation->addGroup($group);

        $helper = self::createStub(DocumentHelperInterface::class);
        $report = new CalculationReport($helper, $calculation, 1.1, 'qrcode');
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws ValidationException
     */
    public function testRender(): void
    {
        $calculation = new Calculation();
        $calculation->withDescription('description')
            ->withCustomer('customer')
            ->withOverallTotal(1000.0)
            ->withGlobalMargin(1.2)
            ->withItemsTotal(800.0)
            ->withUserMargin(0.1);

        $group = new CalculationGroup();
        $group->withCode('Group');

        $category = new CalculationCategory();
        $category->withCode('Category');

        $item = new CalculationItem();
        $item->withDescription('Description')
            ->withPrice(100.0)
            ->withQuantity(0.0);
        $category->addItem($item);
        $group->addCategory($category);
        $calculation->addGroup($group);

        $helper = self::createStub(DocumentHelperInterface::class);
        $report = new CalculationReport($helper, $calculation, 2.0, 'qrcode');
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws ValidationException
     */
    public function testRenderWithoutQrCode(): void
    {
        $calculation = new Calculation();
        $calculation->withDescription('description')
            ->withCustomer('customer')
            ->withOverallTotal(1000.0)
            ->withGlobalMargin(1.2)
            ->withItemsTotal(800.0)
            ->withUserMargin(0.1);

        $group = new CalculationGroup();
        $group->withCode('Group');

        $category = new CalculationCategory();
        $category->withCode('Category');

        $item = new CalculationItem();
        $item->withDescription('Description')
            ->withPrice(100.0)
            ->withQuantity(0.0);
        $category->addItem($item);
        $group->addCategory($category);
        $calculation->addGroup($group);

        $helper = self::createStub(DocumentHelperInterface::class);
        $report = new CalculationReport($helper, $calculation, 2.0, '');
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
