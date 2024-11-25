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
use App\Report\HtmlColorNameReport;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class HtmlColorNameReportTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testEmpty(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $report = new HtmlColorNameReport($controller);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
