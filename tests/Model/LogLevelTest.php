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
    /**
     * @phpstan-return \Generator<int, array{PsrLevel::*, string}>
     */
    public static function getLevelBorders(): \Generator
    {
        yield [PsrLevel::EMERGENCY, 'text-border-danger'];
        yield [PsrLevel::ALERT, 'text-border-danger'];
        yield [PsrLevel::CRITICAL, 'text-border-danger'];
        yield [PsrLevel::ERROR, 'text-border-danger'];
        yield [PsrLevel::WARNING, 'text-border-warning'];
        yield [PsrLevel::NOTICE, 'text-border-info'];
        yield [PsrLevel::INFO, 'text-border-info'];
        yield [PsrLevel::DEBUG, 'text-border-secondary'];
    }

    /**
     * @phpstan-return \Generator<int, array{PsrLevel::*, string}>
     */
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

    /**
     * @phpstan-return \Generator<int, array{PsrLevel::*, string}>
     */
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

    public function testIncrement(): void
    {
        $logLevel = LogLevel::instance(PsrLevel::WARNING);
        self::assertCount(0, $logLevel);
        $logLevel->increment();
        self::assertCount(1, $logLevel);
        $logLevel->increment(2);
        self::assertCount(3, $logLevel);
    }

    public function testInstance(): void
    {
        $expected = PsrLevel::WARNING;
        $logLevel = LogLevel::instance($expected);
        self::assertSame($expected, $logLevel->getLevel());
        self::assertSame($expected, $logLevel->__toString());
        self::assertSame('Warning', $logLevel->getLevelTitle());
        self::assertCount(0, $logLevel);
    }

    /**
     * @phpstan-param PsrLevel::* $level
     */
    #[DataProvider('getLevelBorders')]
    public function testLevelBorder(string $level, string $expected): void
    {
        $logLevel = LogLevel::instance($level);
        $actual = $logLevel->getLevelBorder();
        self::assertSame($expected, $actual);
    }

    /**
     * @phpstan-param PsrLevel::* $level
     */
    #[DataProvider('getLevelColors')]
    public function testLevelColor(string $level, string $expected): void
    {
        $logLevel = LogLevel::instance($level);
        $actual = $logLevel->getLevelColor();
        self::assertSame($expected, $actual);
    }

    /**
     * @phpstan-param PsrLevel::* $level
     */
    #[DataProvider('getLevelIcons')]
    public function testLevelIcon(string $level, string $expected): void
    {
        $logLevel = LogLevel::instance($level);
        $actual = $logLevel->getLevelIcon();
        self::assertSame($expected, $actual);
    }
}
