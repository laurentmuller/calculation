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

namespace App\Tests\Service;

use App\Entity\Log;
use App\Service\LogFilterService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel as PsrLevel;
use Symfony\Component\Clock\DatePoint;

final class LogFilterServiceTest extends TestCase
{
    public function testByChannel(): void
    {
        $log1 = Log::instance(1)
            ->setCreatedAt(new DatePoint('2024-01-01'))
            ->setChannel('channel1');
        $log2 = Log::instance(1)
            ->setCreatedAt(new DatePoint('2024-02-02'))
            ->setChannel('channel2');
        $filter = LogFilterService::instance(channel: 'channel1');
        $this->assertSameLogs($filter, [$log1, $log2], $log1);
    }

    public function testByLevel(): void
    {
        $log1 = Log::instance(1)
            ->setCreatedAt(new DatePoint('2024-01-01'))
            ->setLevel(PsrLevel::ALERT);
        $log2 = Log::instance(1)
            ->setCreatedAt(new DatePoint('2024-02-02'))
            ->setLevel(PsrLevel::INFO);
        $filter = LogFilterService::instance(level: PsrLevel::ALERT);
        $this->assertSameLogs($filter, [$log1, $log2], $log1);
    }

    public function testByNone(): void
    {
        $log1 = Log::instance(1)
            ->setCreatedAt(new DatePoint('2024-01-01'))
            ->setMessage('message');
        $log2 = Log::instance(1)
            ->setCreatedAt(new DatePoint('2024-02-02'))
            ->setMessage('message');
        $filter = LogFilterService::instance();
        $this->assertSameLogs($filter, [$log1, $log2], $log1, $log2);
    }

    public function testByUser(): void
    {
        $log1 = Log::instance(1)
            ->setCreatedAt(new DatePoint('2024-01-01'))
            ->setUser('user1');
        $log2 = Log::instance(1)
            ->setCreatedAt(new DatePoint('2024-02-02'))
            ->setUser('user2');
        $filter = LogFilterService::instance(value: 'user1');
        $this->assertSameLogs($filter, [$log1, $log2], $log1);
    }

    public function testByValue(): void
    {
        $log1 = Log::instance(1)
            ->setCreatedAt(new DatePoint('2024-01-01'))
            ->setMessage('message1');
        $log2 = Log::instance(1)
            ->setCreatedAt(new DatePoint('2024-02-02'))
            ->setMessage('message2');
        $filter = LogFilterService::instance(value: 'message1');
        $this->assertSameLogs($filter, [$log1, $log2], $log1);
    }

    public function testIsFilter(): void
    {
        $actual = LogFilterService::isFilter();
        self::assertFalse($actual);
        $actual = LogFilterService::isFilter(value: 'value');
        self::assertTrue($actual);
        $actual = LogFilterService::isFilter(level: 'level');
        self::assertTrue($actual);
        $actual = LogFilterService::isFilter(channel: 'channel');
        self::assertTrue($actual);
    }

    /**
     * @param Log[] $logs
     */
    private function assertSameLogs(LogFilterService $filter, array $logs, Log ...$expected): void
    {
        $actual = $filter->filter($logs);
        self::assertSame($expected, $actual);
    }
}
