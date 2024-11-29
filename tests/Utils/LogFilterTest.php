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

namespace App\Tests\Utils;

use App\Entity\Log;
use App\Utils\LogFilter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel as PsrLevel;

class LogFilterTest extends TestCase
{
    public function testFilterByChannel(): void
    {
        $log1 = Log::instance(1)
            ->setCreatedAt(new \DateTimeImmutable('2024-01-01'))
            ->setChannel('channel');
        $log2 = Log::instance(1)
            ->setCreatedAt(new \DateTimeImmutable('2024-02-02'))
            ->setChannel('channel');
        $logs = [$log1, $log2];

        $filter = new LogFilter('', '', 'channel');
        $filter->filter($logs);
        self::assertSame([$log1, $log2], $logs);
    }

    public function testFilterByLevel(): void
    {
        $log1 = Log::instance(1)
            ->setCreatedAt(new \DateTimeImmutable('2024-01-01'))
            ->setLevel(PsrLevel::ALERT);
        $log2 = Log::instance(1)
            ->setCreatedAt(new \DateTimeImmutable('2024-02-02'))
            ->setLevel(PsrLevel::ALERT);
        $logs = [$log1, $log2];

        $filter = new LogFilter('', PsrLevel::ALERT, '');
        $filter->filter($logs);
        self::assertSame([$log1, $log2], $logs);
    }

    public function testFilterByMessage(): void
    {
        $log1 = Log::instance(1)
            ->setCreatedAt(new \DateTimeImmutable('2024-01-01'))
            ->setMessage('message');
        $log2 = Log::instance(1)
            ->setCreatedAt(new \DateTimeImmutable('2024-02-02'))
            ->setMessage('message');
        $logs = [$log1, $log2];

        $filter = new LogFilter('message', '', '');
        $filter->filter($logs);
        self::assertSame([$log1, $log2], $logs);
    }

    public function testFilterByUser(): void
    {
        $log1 = Log::instance(1)
            ->setCreatedAt(new \DateTimeImmutable('2024-01-01'))
            ->setExtra(['user' => 'user']);
        $log2 = Log::instance(1)
            ->setCreatedAt(new \DateTimeImmutable('2024-02-02'))
            ->setExtra(['user' => 'user']);
        $logs = [$log1, $log2];

        $filter = new LogFilter('user', '', '');
        $filter->filter($logs);
        self::assertSame([$log1, $log2], $logs);
    }

    public function testFilterNone(): void
    {
        $log1 = Log::instance(1)
            ->setCreatedAt(new \DateTimeImmutable('2024-01-01'))
            ->setMessage('message');
        $log2 = Log::instance(1)
            ->setCreatedAt(new \DateTimeImmutable('2024-02-02'))
            ->setMessage('message');
        $logs = [$log1, $log2];

        $filter = new LogFilter('', '', '');
        $filter->filter($logs);
        self::assertSame([$log1, $log2], $logs);
    }

    public function testIsFilter(): void
    {
        $actual = LogFilter::isFilter('', '', '');
        self::assertFalse($actual);
        $actual = LogFilter::isFilter('value', '', '');
        self::assertTrue($actual);
        $actual = LogFilter::isFilter('', 'level', '');
        self::assertTrue($actual);
        $actual = LogFilter::isFilter('', '', 'channel');
        self::assertTrue($actual);
    }
}
