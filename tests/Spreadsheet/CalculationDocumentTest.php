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

namespace App\Tests\Spreadsheet;

use App\Entity\Calculation;
use App\Entity\CalculationCategory;
use App\Entity\CalculationGroup;
use App\Entity\CalculationItem;
use App\Interfaces\DocumentHelperInterface;
use App\Spreadsheet\CalculationDocument;
use PHPUnit\Framework\TestCase;

final class CalculationDocumentTest extends TestCase
{
    public function testRender(): void
    {
        $calculation = new Calculation();
        $calculation->withDescription('description')
            ->withCustomer('customer')
            ->withOverallTotal(1000.0)
            ->withGlobalMargin(1.0)
            ->withItemsTotal(1000.0)
            ->withUserMargin(0.1);

        $group = new CalculationGroup();
        $group->withCode('Group');

        $category = new CalculationCategory();
        $category->withCode('Category');

        $item = new CalculationItem();
        $item->withDescription('Description');
        $group->addCategory($category);
        $category->addItem($item);
        $calculation->addGroup($group);

        $helper = self::createStub(DocumentHelperInterface::class);
        $document = new CalculationDocument($helper, $calculation);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    public function testRenderEmpty(): void
    {
        $calculation = new Calculation();
        $calculation->withDescription('description')
            ->withCustomer('customer')
            ->withOverallTotal(1000.0)
            ->withGlobalMargin(1.0)
            ->withItemsTotal(1000.0)
            ->withUserMargin(0.1);

        $helper = self::createStub(DocumentHelperInterface::class);
        $document = new CalculationDocument($helper, $calculation);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    public function testRenderUserMarginZero(): void
    {
        $calculation = new Calculation();
        $calculation->withDescription('description')
            ->withCustomer('customer')
            ->withOverallTotal(1000.0)
            ->withGlobalMargin(1.0)
            ->withItemsTotal(1000.0);

        $group = new CalculationGroup();
        $group->withCode('Group');

        $category = new CalculationCategory();
        $category->withCode('Category');

        $item = new CalculationItem();
        $item->withDescription('Description');
        $group->addCategory($category);
        $category->addItem($item);
        $calculation->addGroup($group);

        $helper = self::createStub(DocumentHelperInterface::class);
        $document = new CalculationDocument($helper, $calculation);
        $actual = $document->render();
        self::assertTrue($actual);
    }
}
