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
use App\Entity\Category;
use App\Entity\Group;
use App\Entity\Product;
use App\Report\ProductsReport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProductsReport::class)]
class ProductsReportTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testRender(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $group = new Group();
        $group->setCode('Group');
        $category = new Category();
        $category->setCode('Category');
        $product = new Product();
        $product->setDescription('description');

        $group->addCategory($category);
        $category->addProduct($product);

        $report = new ProductsReport($controller, [$product]);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
