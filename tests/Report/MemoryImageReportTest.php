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
use App\Report\MemoryImageReport;
use fpdf\PdfException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(MemoryImageReport::class)]
class MemoryImageReportTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testEmptyImage(): void
    {
        self::expectException(PdfException::class);
        $controller = $this->createMock(AbstractController::class);
        $image = __DIR__ . '/../Data/empty.txt';
        $report = new MemoryImageReport($controller, $image);
        $report->render();
    }

    /**
     * @throws Exception
     */
    public function testInvalidImage(): void
    {
        self::expectException(PdfException::class);
        $controller = $this->createMock(AbstractController::class);
        $report = new MemoryImageReport($controller, __FILE__);
        $report->render();
    }

    /**
     * @throws Exception
     */
    public function testRender(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $image = __DIR__ . '/../Data/images/example.png';
        $report = new MemoryImageReport($controller, $image);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
