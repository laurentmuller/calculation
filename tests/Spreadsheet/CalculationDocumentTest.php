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

use App\Controller\AbstractController;
use App\Entity\Calculation;
use App\Entity\CalculationCategory;
use App\Entity\CalculationGroup;
use App\Entity\CalculationItem;
use App\Spreadsheet\CalculationDocument;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class CalculationDocumentTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testRender(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('description')
            ->setCustomer('customer')
            ->setOverallTotal(1000.0)
            ->setGlobalMargin(1.0)
            ->setItemsTotal(1000.0)
            ->setUserMargin(0.1);

        $group = new CalculationGroup();
        $group->setCode('Group');

        $category = new CalculationCategory();
        $category->setCode('Category');

        $item = new CalculationItem();
        $item->setDescription('Description');
        $group->addCategory($category);
        $category->addItem($item);
        $calculation->addGroup($group);

        $controller = $this->createMock(AbstractController::class);
        $document = new CalculationDocument($controller, $calculation);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRenderEmpty(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('description')
            ->setCustomer('customer')
            ->setOverallTotal(1000.0)
            ->setGlobalMargin(1.0)
            ->setItemsTotal(1000.0)
            ->setUserMargin(0.1);

        $controller = $this->createMock(AbstractController::class);
        $document = new CalculationDocument($controller, $calculation);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRenderUserMarginZero(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('description')
            ->setCustomer('customer')
            ->setOverallTotal(1000.0)
            ->setGlobalMargin(1.0)
            ->setItemsTotal(1000.0);

        $group = new CalculationGroup();
        $group->setCode('Group');

        $category = new CalculationCategory();
        $category->setCode('Category');

        $item = new CalculationItem();
        $item->setDescription('Description');
        $group->addCategory($category);
        $category->addItem($item);
        $calculation->addGroup($group);

        $controller = $this->createMock(AbstractController::class);
        $document = new CalculationDocument($controller, $calculation);
        $actual = $document->render();
        self::assertTrue($actual);
    }
}
