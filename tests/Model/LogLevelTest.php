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
use App\Pdf\Html\HtmlBootstrapColor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel as PsrLevel;

final class LogLevelTest extends TestCase
{
    public static function getLevelBootstrapColors(): \Generator
    {
        yield [PsrLevel::EMERGENCY, HtmlBootstrapColor::DANGER];
        yield [PsrLevel::ALERT,  HtmlBootstrapColor::DANGER];
        yield [PsrLevel::CRITICAL,  HtmlBootstrapColor::DANGER];
        yield [PsrLevel::ERROR,  HtmlBootstrapColor::DANGER];
        yield [PsrLevel::WARNING,  HtmlBootstrapColor::WARNING];
        yield [PsrLevel::NOTICE,  HtmlBootstrapColor::INFO];
        yield [PsrLevel::INFO,   HtmlBootstrapColor::INFO];
        yield [PsrLevel::DEBUG,   HtmlBootstrapColor::SECONDARY];
    }

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
        yield [PsrLevel::EMERGENCY, 'fa-solid fa-circle-exclamation fa-fw'];
        yield [PsrLevel::ALERT, 'fa-solid fa-circle-exclamation fa-fw'];
        yield [PsrLevel::CRITICAL, 'fa-solid fa-circle-exclamation fa-fw'];
        yield [PsrLevel::ERROR, 'fa-solid fa-circle-exclamation fa-fw'];
        yield [PsrLevel::WARNING, 'fa-solid fa-triangle-exclamation fa-fw'];
        yield [PsrLevel::NOTICE, 'fa-solid fa-circle-info fa-fw'];
        yield [PsrLevel::INFO, 'fa-solid fa-circle-info fa-fw'];
        yield [PsrLevel::DEBUG, 'fa-solid fa-circle-info fa-fw'];
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
        self::assertSame($expected, (string) $logLevel);
        self::assertSame('Warning', $logLevel->getLevelTitle());
        self::assertCount(0, $logLevel);
    }

    /**
     * @phpstan-param PsrLevel::* $level
     */
    #[DataProvider('getLevelBootstrapColors')]
    public function testLevelBootstrapColor(string $level, HtmlBootstrapColor $expected): void
    {
        $logLevel = LogLevel::instance($level);
        $actual = $logLevel->getLevelBootstrapColor();
        self::assertSame($expected, $actual);
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
