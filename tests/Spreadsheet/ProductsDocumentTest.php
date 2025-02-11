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
use App\Entity\Category;
use App\Entity\Group;
use App\Entity\Product;
use App\Spreadsheet\ProductsDocument;
use PHPUnit\Framework\TestCase;

class ProductsDocumentTest extends TestCase
{
    public function testRender(): void
    {
        $group = new Group();
        $group->setCode('Group');

        $category = new Category();
        $category->setCode('Category');

        $product = new Product();
        $product->setDescription('Description');

        $group->addCategory($category);
        $category->addProduct($product);

        $controller = $this->createMock(AbstractController::class);
        $document = new ProductsDocument($controller, [$product]);
        $actual = $document->render();
        self::assertTrue($actual);
    }
}
