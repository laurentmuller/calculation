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

namespace App\Tests\Entity;

use App\Entity\Log;
use App\Tests\DateAssertTrait;
use App\Utils\DateUtils;
use App\Utils\FormatUtils;
use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel as PsrLevel;
use Symfony\Component\Clock\DatePoint;

final class LogTest extends TestCase
{
    use DateAssertTrait;

    public static function getChannelIcons(): \Generator
    {
        yield ['application', 'fa-fw fa-solid fa-laptop-code'];
        yield ['cache', 'fa-fw fa-solid fa-hard-drive'];
        yield ['console', 'fa-fw fa-solid fa-keyboard'];
        yield ['doctrine', 'fa-fw fa-solid fa-database'];
        yield ['mailer', 'fa-fw fa-solid fa-envelope'];
        yield ['php', 'fa-fw fa-solid fa-code'];
        yield ['request', 'fa-fw fa-solid fa-upload'];
        yield ['security', 'fa-fw fa-solid fa-key'];
        yield ['deprecation', 'fa-fw fa-solid fa-bug'];
    }

    public static function getLevelColors(): \Generator
    {
        yield [PsrLevel::EMERGENCY, 'text-danger'];
        yield [PsrLevel::ALERT, 'text-danger'];
        yield [PsrLevel::CRITICAL, 'text-danger'];
        yield [PsrLevel::ERROR, 'text-danger'];
        yield [PsrLevel::WARNING, 'text-warning'];
        yield [PsrLevel::NOTICE, 'text-info'];
        yield [PsrLevel::INFO, 'text-info'];
        yield [PsrLevel::DEBUG, 'text-secondary'];
    }

    public static function getLevelIcons(): \Generator
    {
        yield [PsrLevel::EMERGENCY, 'fa-fw fa-solid fa-circle-exclamation'];
        yield [PsrLevel::ALERT, 'fa-fw fa-solid fa-circle-exclamation'];
        yield [PsrLevel::CRITICAL, 'fa-fw fa-solid fa-circle-exclamation'];
        yield [PsrLevel::ERROR, 'fa-fw fa-solid fa-circle-exclamation'];
        yield [PsrLevel::WARNING, 'fa-fw fa-solid fa-triangle-exclamation'];
        yield [PsrLevel::NOTICE, 'fa-fw fa-solid fa-circle-info'];
        yield [PsrLevel::INFO, 'fa-fw fa-solid fa-circle-info'];
        yield [PsrLevel::DEBUG, 'fa-fw fa-solid fa-circle-info'];
    }

    public function testChannel(): void
    {
        $log = new Log();
        $log->setChannel('channel');
        self::assertSame('channel', $log->getChannel());
        self::assertSame('Channel', $log->getChannelTitle());
    }

    public function testChannelApplication(): void
    {
        $log = new Log();
        self::assertSame('application', $log->getChannel());
        $log->setChannel('channel');
        self::assertSame('channel', $log->getChannel());
        $log->setChannel('app');
        self::assertSame('application', $log->getChannel());
    }

    #[DataProvider('getChannelIcons')]
    public function testChannelIcon(string $channel, string $expected): void
    {
        $log = new Log();
        $log->setChannel($channel);
        $actual = $log->getChannelIcon();
        self::assertSame($expected, $actual);
    }

    public function testCompare(): void
    {
        $date = new DatePoint();
        $log1 = new Log();
        $log1->setCreatedAt($date);
        $log2 = new Log();
        $log2->setCreatedAt($date);
        self::assertSame(0, $log1->compare($log2));
        $date = DateUtils::sub($date, 'PT1H');
        $log2->setCreatedAt($date);
        self::assertSame(1, $log1->compare($log2));
    }

    public function testContext(): void
    {
        $log = new Log();
        self::assertNull($log->getContext());

        $expected = ['key' => 'value'];
        $log->setContext($expected);
        self::assertSame($expected, $log->getContext());
    }

    public function testDisplayAndMessage(): void
    {
        $log = new Log();
        self::assertSame('', $log->getDisplay());
        self::assertSame('', $log->getMessage());

        $log->setMessage('message');
        self::assertSame('message', $log->getDisplay());
        self::assertSame('message', $log->getMessage());
    }

    public function testDoctrineMessage(): void
    {
        $log = new Log();
        $log->setMessage('message');
        $log->setChannel('doctrine');
        $actual = $log->isDoctrineMessage();
        self::assertFalse($actual);

        $log->setMessage('Executing statement: SELECT * FROM table');
        $actual = $log->isDoctrineMessage();
        self::assertTrue($actual);

        $log->setMessage('Executing query: SELECT * FROM table');
        $actual = $log->isDoctrineMessage();
        self::assertTrue($actual);
    }

    public function testFormatMessageDefault(): void
    {
        $log = new Log();
        $log->setMessage('message');
        $formatter = $this->createSqlFormatter();
        $actual = $log->formatMessage($formatter);
        self::assertSame('message', $actual);
    }

    public function testFormatMessageWithContext(): void
    {
        $log = new Log();
        $log->setMessage('message');
        $log->setContext(['key' => 'value']);
        $formatter = $this->createSqlFormatter();
        $actual = $log->formatMessage($formatter);
        self::assertSame("message\nContext:\n[\n  'key' => 'value'\n]", $actual);
    }

    public function testFormatMessageWithDoctrine(): void
    {
        $log = new Log();
        $log->setChannel('doctrine');
        $log->setMessage('Executing statement');
        $formatter = $this->createSqlFormatter();
        $actual = $log->formatMessage($formatter);
        self::assertSame('Executing statement', $actual);
    }

    public function testFormatMessageWithUser(): void
    {
        $log = new Log();
        $log->setMessage('message');
        $log->setUser('username');
        $formatter = $this->createSqlFormatter();
        $actual = $log->formatMessage($formatter);
        self::assertSame("message\nUser:\nusername", $actual);
    }

    public function testFormattedDate(): void
    {
        $date = new DatePoint();
        $log = new Log();
        $log->setCreatedAt($date);
        $actual = $log->getCreatedAt();
        self::assertTimestampEquals($date, $actual);
        $expected = FormatUtils::formatDateTime($date, \IntlDateFormatter::SHORT, \IntlDateFormatter::MEDIUM);
        $actual = $log->getFormattedDate();
        self::assertSame($expected, $actual);
    }

    public function testInstance(): void
    {
        $log = Log::instance();
        self::assertNull($log->getId());
        $log = Log::instance(15);
        self::assertSame(15, $log->getId());
    }

    public function testLevel(): void
    {
        $log = new Log();
        $log->setLevel(PsrLevel::ALERT);
        self::assertSame('alert', $log->getLevel());
        self::assertSame('Alert', $log->getLevelTitle());
    }

    /**
     * @phpstan-param PsrLevel::* $level
     */
    #[DataProvider('getLevelColors')]
    public function testLevelColor(string $level, string $expected): void
    {
        $log = new Log();
        $log->setLevel($level);
        $actual = $log->getLevelColor();
        self::assertSame($expected, $actual);
    }

    /**
     * @phpstan-param PsrLevel::* $level
     */
    #[DataProvider('getLevelIcons')]
    public function testLevelIcon(string $level, string $expected): void
    {
        $log = new Log();
        $log->setLevel($level);
        $actual = $log->getLevelIcon();
        self::assertSame($expected, $actual);
    }

    public function testTimestamp(): void
    {
        $expected = new DatePoint();
        $log = new Log();
        $log->setCreatedAt($expected);
        $actual = $log->getTimestamp();
        self::assertTimestampEquals($expected, $actual);
    }

    public function testUser(): void
    {
        $log = new Log();
        self::assertNull($log->getUser());

        $expected = 'my_user_name';
        $log->setUser($expected);
        self::assertSame($expected, $log->getUser());
    }

    private function createSqlFormatter(): SqlFormatter
    {
        return new SqlFormatter(new NullHighlighter());
    }
}
