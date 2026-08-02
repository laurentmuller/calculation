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

namespace App\Tests\Model;

use App\Model\LogChannel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LogChannelTest extends TestCase
{
    public static function getChannelIcons(): \Generator
    {
        yield ['application', 'fa-solid fa-laptop-code fa-fw'];
        yield ['cache', 'fa-solid fa-hard-drive fa-fw'];
        yield ['console', 'fa-solid fa-keyboard fa-fw'];
        yield ['doctrine', 'fa-solid fa-database fa-fw'];
        yield ['mailer', 'fa-solid fa-envelope fa-fw'];
        yield ['php', 'fa-solid fa-code fa-fw'];
        yield ['request', 'fa-solid fa-upload fa-fw'];
        yield ['security', 'fa-solid fa-key fa-fw'];
        yield ['deprecation', 'fa-solid fa-bug fa-fw'];
    }

    #[DataProvider('getChannelIcons')]
    public function testChannelIcon(string $channel, string $expected): void
    {
        $logChannel = LogChannel::instance('channel');
        $logChannel->setChannel($channel);
        $actual = $logChannel->getChannelIcon();
        self::assertSame($expected, $actual);
    }

    public function testIncrement(): void
    {
        $logChannel = LogChannel::instance('channel');
        self::assertCount(0, $logChannel);
        $logChannel->increment();
        self::assertCount(1, $logChannel);
        $logChannel->increment(2);
        self::assertCount(3, $logChannel);
    }

    public function testInstance(): void
    {
        $logChannel = LogChannel::instance('channel');
        self::assertSame('channel', $logChannel->getChannel());
        self::assertSame('channel', (string) $logChannel);
        self::assertSame('Channel', $logChannel->getChannelTitle());
        self::assertCount(0, $logChannel);
    }
}
