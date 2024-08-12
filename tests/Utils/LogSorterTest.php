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
use App\Utils\LogSorter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class LogSorterTest extends TestCase
{
    public function testIsDefaultSort(): void
    {
        self::assertTrue(LogSorter::isDefaultSort('createdAt', false));
        self::assertFalse(LogSorter::isDefaultSort('message', true));
    }

    public function testSortByChannel(): void
    {
        $log1 = Log::instance(1)
            ->setChannel('ChannelA');
        $log2 = Log::instance(1)
            ->setChannel('ChannelB');
        $logs = [$log1, $log2];

        $sorter = new LogSorter('channel', true);
        $sorter->sort($logs);
        self::assertSame([$log1, $log2], $logs);
    }

    public function testSortByChannelAndDate(): void
    {
        $log1 = Log::instance(1)
            ->setCreatedAt(new \DateTime('2024-02-01'))
            ->setChannel('ChannelA');
        $log2 = Log::instance(1)
            ->setCreatedAt(new \DateTime('2024-01-02'))
            ->setChannel('ChannelA');
        $logs = [$log1, $log2];

        $sorter = new LogSorter('channel', true);
        $sorter->sort($logs);
        self::assertSame([$log1, $log2], $logs);
    }

    public function testSortByDate(): void
    {
        $log1 = Log::instance(1)
            ->setCreatedAt(new \DateTime('2024-01-01'));
        $log2 = Log::instance(1)
            ->setCreatedAt(new \DateTime('2024-02-02'));
        $logs = [$log1, $log2];

        $sorter = new LogSorter('createdAt', true);
        $sorter->sort($logs);
        self::assertSame([$log1, $log2], $logs);
    }

    public function testSortByLevel(): void
    {
        $log1 = Log::instance(1)
            ->setLevel(LogLevel::ALERT);
        $log2 = Log::instance(1)
            ->setLevel(LogLevel::DEBUG);
        $logs = [$log1, $log2];

        $sorter = new LogSorter('level', true);
        $sorter->sort($logs);
        self::assertSame([$log1, $log2], $logs);
    }

    public function testSortByMessage(): void
    {
        $log1 = Log::instance(1)
            ->setMessage('Message 1');
        $log2 = Log::instance(2)
            ->setMessage('Message 2');
        $logs = [$log1, $log2];

        $sorter = new LogSorter('message', true);
        $sorter->sort($logs);
        self::assertSame([$log1, $log2], $logs);
    }

    public function testSortByUser(): void
    {
        $log1 = Log::instance(1)
            ->setExtra(['user' => 'user1']);
        $log2 = Log::instance(2)
            ->setExtra(['user' => 'user2']);

        $logs = [$log1, $log2];

        $sorter = new LogSorter('user', true);
        $sorter->sort($logs);
        self::assertSame([$log1, $log2], $logs);
    }

    public function testSortEmpty(): void
    {
        $logs = [];
        $sorter = new LogSorter('createdAt', false);
        $sorter->sort($logs);
        self::assertSame([], $logs);
    }

    public function testWithoutFieldSorter(): void
    {
        $log1 = Log::instance(1)
            ->setCreatedAt(new \DateTime('2024-02-02'));
        $log2 = Log::instance(1)
            ->setCreatedAt(new \DateTime('2024-01-01'));
        $logs = [$log1, $log2];

        $sorter = new LogSorter('fake', true);
        $sorter->sort($logs);
        self::assertSame([$log1, $log2], $logs);
    }
}
