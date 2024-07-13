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
use App\Entity\Group;
use App\Entity\GroupMargin;
use App\Report\GroupsReport;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class GroupsReportTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testRender(): void
    {
        $controller = $this->createMock(AbstractController::class);

        $group1 = new Group();
        $group1->setCode('Group1');
        $margin1 = new GroupMargin();
        $group1->addMargin($margin1);
        $margin2 = new GroupMargin();
        $group1->addMargin($margin2);

        $group2 = new Group();
        $group2->setCode('Group2');

        $report = new GroupsReport($controller, [$group1, $group2]);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
