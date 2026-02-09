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
use App\Entity\Log;
use App\Model\LogChannel;
use App\Model\LogFile;
use App\Model\LogLevel;
use App\Spreadsheet\LogsDocument;
use App\Utils\DateUtils;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel as PsrLevel;

final class LogsDocumentTest extends TestCase
{
    public function testEmpty(): void
    {
        $controller = self::createStub(AbstractController::class);
        $logFile = $this->createMock(LogFile::class);
        $logFile->method('isEmpty')
            ->willReturn(true);

        $report = new LogsDocument($controller, $logFile);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRender(): void
    {
        $controller = self::createStub(AbstractController::class);

        $log1 = Log::instance(1);
        $level1 = new LogLevel($log1->getLevel());
        $channel1 = new LogChannel($log1->getChannel());

        $log2 = Log::instance(2);
        $log2->setCreatedAt(DateUtils::add($log1->getCreatedAt(), 'P7D'))
            ->setLevel(PsrLevel::ALERT)
            ->setChannel('doctrine');
        $level2 = new LogLevel($log2->getLevel());
        $channel2 = new LogChannel($log2->getChannel());

        $log3 = Log::instance(2);
        $log3->setCreatedAt(DateUtils::add($log2->getCreatedAt(), 'P7D'))
            ->setLevel(PsrLevel::ALERT)
            ->setChannel('doctrine');

        $log4 = Log::instance(4);
        $log4->setCreatedAt(DateUtils::add($log1->getCreatedAt(), 'P7D'))
            ->setLevel(PsrLevel::WARNING)
            ->setChannel('doctrine');

        $log5 = Log::instance(5);
        $log5->setCreatedAt(DateUtils::add($log1->getCreatedAt(), 'P7D'))
            ->setLevel(PsrLevel::DEBUG)
            ->setChannel('doctrine');

        $logFile = $this->createMock(LogFile::class);
        $logFile->method('isEmpty')
            ->willReturn(false);
        $logFile->method('getLogs')
            ->willReturn([$log1, $log2, $log3, $log4, $log5]);
        $logFile->method('getLevels')
            ->willReturn([
                'info' => $level1,
                'alert' => $level2,
            ]);
        $logFile->method('getChannels')
            ->willReturn([
                $log1->getChannel() => $channel1,
                $log2->getChannel() => $channel2,
            ]);

        $report = new LogsDocument($controller, $logFile);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
