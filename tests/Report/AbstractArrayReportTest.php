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

use App\Interfaces\DocumentHelperInterface;
use App\Report\AbstractArrayReport;
use PHPUnit\Framework\TestCase;

final class AbstractArrayReportTest extends TestCase
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
     * @phpstan-ignore missingType.generics
     */
    private function createReport(array $entities = []): AbstractArrayReport
    {
        $helper = self::createStub(DocumentHelperInterface::class);

        return new class($helper, $entities) extends AbstractArrayReport {
            #[\Override]
            protected function doRender(array $entities): bool
            {
                return true;
            }
        };
    }
}
