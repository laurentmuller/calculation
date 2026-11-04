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
            ->withCreatedAt(new DatePoint('2024-01-01'))
            ->withChannel('channel1');
        $log2 = Log::instance(1)
            ->withCreatedAt(new DatePoint('2024-02-02'))
            ->withChannel('channel2');
        $filter = LogFilterService::instance(channel: 'channel1');
        self::assertSameLogs($filter, [$log1, $log2], $log1);
    }

    public function testByLevel(): void
    {
        $log1 = Log::instance(1)
            ->withCreatedAt(new DatePoint('2024-01-01'))
            ->withLevel(PsrLevel::ALERT);
        $log2 = Log::instance(1)
            ->withCreatedAt(new DatePoint('2024-02-02'))
            ->withLevel(PsrLevel::INFO);
        $filter = LogFilterService::instance(level: PsrLevel::ALERT);
        self::assertSameLogs($filter, [$log1, $log2], $log1);
    }

    public function testByNone(): void
    {
        $log1 = Log::instance(1)
            ->withCreatedAt(new DatePoint('2024-01-01'))
            ->withMessage('message');
        $log2 = Log::instance(1)
            ->withCreatedAt(new DatePoint('2024-02-02'))
            ->withMessage('message');
        $filter = LogFilterService::instance();
        self::assertSameLogs($filter, [$log1, $log2], $log1, $log2);
    }

    public function testByUser(): void
    {
        $log1 = Log::instance(1)
            ->withCreatedAt(new DatePoint('2024-01-01'))
            ->withUser('user1');
        $log2 = Log::instance(1)
            ->withCreatedAt(new DatePoint('2024-02-02'))
            ->withUser('user2');
        $filter = LogFilterService::instance(value: 'user1');
        self::assertSameLogs($filter, [$log1, $log2], $log1);
    }

    public function testByValue(): void
    {
        $log1 = Log::instance(1)
            ->withCreatedAt(new DatePoint('2024-01-01'))
            ->withMessage('message1');
        $log2 = Log::instance(1)
            ->withCreatedAt(new DatePoint('2024-02-02'))
            ->withMessage('message2');
        $filter = LogFilterService::instance(value: 'message1');
        self::assertSameLogs($filter, [$log1, $log2], $log1);
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
    protected static function assertSameLogs(LogFilterService $filter, array $logs, Log ...$expected): void
    {
        $actual = $filter->filter($logs);
        self::assertSame($expected, $actual);
    }
}
