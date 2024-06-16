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
use App\Spreadsheet\CategoriesDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(CategoriesDocument::class)]
class CategoriesDocumentTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testRender(): void
    {
        $group = new Group();
        $group->setCode('Group');

        $category = new Category();
        $category->setCode('Category');
        $group->addCategory($category);

        $controller = $this->createMock(AbstractController::class);
        $document = new CategoriesDocument($controller, [$category]);
        $actual = $document->render();
        self::assertTrue($actual);
    }
}
