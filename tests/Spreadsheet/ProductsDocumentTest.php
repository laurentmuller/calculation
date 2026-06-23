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

use App\Entity\Category;
use App\Entity\Group;
use App\Entity\Product;
use App\Interfaces\DocumentHelperInterface;
use App\Spreadsheet\ProductsDocument;
use PHPUnit\Framework\TestCase;

final class ProductsDocumentTest extends TestCase
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

        $helper = self::createStub(DocumentHelperInterface::class);
        $document = new ProductsDocument($helper, [$product]);
        $actual = $document->render();
        self::assertTrue($actual);
    }
}
