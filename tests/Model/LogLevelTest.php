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

use App\Model\LogLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel as PsrLevel;

class LogLevelTest extends TestCase
{
    public static function getLevelColors(): \Generator
    {
        yield [PsrLevel::ALERT, 'text-danger'];
        yield [PsrLevel::CRITICAL, 'text-danger'];
        yield [PsrLevel::EMERGENCY, 'text-danger'];
        yield [PsrLevel::ERROR, 'text-danger'];
        yield [PsrLevel::WARNING, 'text-warning'];
        yield [PsrLevel::DEBUG, 'text-secondary'];
        yield ['fake', 'text-info'];
        yield ['', 'text-info'];
    }

    public static function getLevelIcons(): \Generator
    {
        yield [PsrLevel::ALERT, 'fa-fw fa-solid fa-circle-exclamation'];
        yield [PsrLevel::CRITICAL, 'fa-fw fa-solid fa-circle-exclamation'];
        yield [PsrLevel::EMERGENCY, 'fa-fw fa-solid fa-circle-exclamation'];
        yield [PsrLevel::ERROR, 'fa-fw fa-solid fa-circle-exclamation'];
        yield [PsrLevel::WARNING, 'fa-fw fa-solid fa-triangle-exclamation'];
        yield ['fake', 'fa-fw fa-solid fa-circle-info'];
        yield ['', 'fa-fw fa-solid fa-circle-info'];
    }

    public function testIncrement(): void
    {
        $logLevel = LogLevel::instance('level');
        self::assertCount(0, $logLevel);
        $logLevel->increment();
        self::assertCount(1, $logLevel);
        $logLevel->increment(2);
        self::assertCount(3, $logLevel);
    }

    public function testInstance(): void
    {
        $logLevel = LogLevel::instance('level');
        self::assertSame('level', $logLevel->getLevel());
        self::assertSame('level', $logLevel->__toString());
        self::assertSame('Level', $logLevel->getLevel(true));
        self::assertCount(0, $logLevel);
    }

    public function testIsLevel(): void
    {
        $logLevel = LogLevel::instance('level');
        self::assertTrue($logLevel->isLevel());
        $logLevel = LogLevel::instance('');
        self::assertFalse($logLevel->isLevel());
    }

    #[DataProvider('getLevelColors')]
    public function testLevelColor(string $level, string $expected): void
    {
        $logLevel = LogLevel::instance('level');
        $logLevel->setLevel($level);
        $actual = $logLevel->getLevelColor();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLevelIcons')]
    public function testLevelIcon(string $level, string $expected): void
    {
        $logLevel = LogLevel::instance('level');
        $logLevel->setLevel($level);
        $actual = $logLevel->getLevelIcon();
        self::assertSame($expected, $actual);
    }
}
