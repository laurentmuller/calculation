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
use App\Entity\Log;
use App\Model\LogChannel;
use App\Model\LogFile;
use App\Model\LogLevel;
use App\Report\LogsReport;
use App\Utils\DateUtils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel as PsrLevel;

#[CoversClass(LogsReport::class)]
class LogsReportTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testEmpty(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $logFile = $this->createMock(LogFile::class);
        $logFile->method('isEmpty')
            ->willReturn(true);

        $report = new LogsReport($controller, $logFile);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testRender(): void
    {
        $controller = $this->createMock(AbstractController::class);

        $log1 = Log::instance(1);
        $logLevel1 = new LogLevel($log1->getLevel());
        $logChannel1 = new LogChannel($log1->getChannel());

        $log2 = Log::instance(2);
        $log2->setCreatedAt(DateUtils::add($log1->getCreatedAt(), 'P7D'))
            ->setLevel(PsrLevel::ALERT)
            ->setChannel('doctrine');
        $logLevel2 = new LogLevel($log2->getLevel());
        $logChannel2 = new LogChannel($log2->getChannel());

        $logFile = $this->createMock(LogFile::class);
        $logFile->method('isEmpty')
            ->willReturn(false);
        $logFile->method('getLogs')
            ->willReturn([$log1, $log2]);
        $logFile->method('getLevels')
            ->willReturn([
                $log1->getLevel() => $logLevel1,
                $log2->getLevel() => $logLevel2,
            ]);
        $logFile->method('getChannels')
            ->willReturn([
                $log1->getChannel() => $logChannel1,
                $log2->getChannel() => $logChannel2,
            ]);

        $report = new LogsReport($controller, $logFile);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
