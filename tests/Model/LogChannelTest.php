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
use Symfony\Component\Validator\Validation;

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
        $logChannel->withChannel($channel);
        $actual = $logChannel->channelIcon;
        self::assertSame($expected, $actual);
    }

    public function testCompare(): void
    {
        $logChannel1 = LogChannel::instance('a');
        $logChannel2 = LogChannel::instance('b');
        $actual = $logChannel1->compare($logChannel2);
        self::assertSame(-1, $actual);
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
        self::assertSame('channel', $logChannel->channel);
        self::assertSame('channel', (string) $logChannel);
        self::assertSame('Channel', $logChannel->channelTitle);
        self::assertCount(0, $logChannel);
    }

    public function testValidation(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $logChannel = LogChannel::instance('channel');
        $errors = $validator->validate($logChannel);
        self::assertCount(0, $errors);

        $logChannel = LogChannel::instance('');
        $errors = $validator->validate($logChannel);
        self::assertCount(1, $errors);
    }
}
