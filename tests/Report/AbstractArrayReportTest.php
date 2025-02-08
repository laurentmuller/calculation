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
use App\Report\AbstractArrayReport;
use PHPUnit\Framework\TestCase;

class AbstractArrayReportTest extends TestCase
{
    public function testRender(): void
    {
        $report = $this->createReport();
        $actual = $report->render();
        self::assertFalse($actual);

        $report = $this->createReport([1, 2, 3]);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @psalm-suppress MissingTemplateParam
     *
     * @phpstan-ignore missingType.generics
     */
    private function createReport(array $entities = []): AbstractArrayReport
    {
        $controller = $this->createMock(AbstractController::class);

        return new class($controller, $entities) extends AbstractArrayReport {
            protected function doRender(array $entities): bool
            {
                return true;
            }
        };
    }
}
