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
use App\Report\CategoriesReport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(CategoriesReport::class)]
class CategoriesReportTest extends TestCase
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
        $category->setCode('Category')
            ->setGroup($group);
        $report = new CategoriesReport($controller, [$category]);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
