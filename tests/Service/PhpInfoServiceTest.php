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

use App\Service\PhpInfoService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhpInfoServiceTest extends TestCase
{
    private PhpInfoService $service;

    #[\Override]
    protected function setUp(): void
    {
        $this->service = new PhpInfoService();
    }

    public static function getColors(): \Generator
    {
        yield ['#0f0f0f', true];
        yield ['#0F0F0F', true];
        yield ['#000000', true];
        yield ['000000', false];
        yield ['fake', false];
    }

    public static function getDisabledValues(): \Generator
    {
        yield ['off', true];
        yield ['OFF', true];
        yield ['no', true];
        yield ['disabled', true];
        yield ['not enabled', true];
        yield ['fake', false];
    }

    public static function getNoValues(): \Generator
    {
        yield ['no value', true];
        yield ['NO VALUE', true];
        yield ['fake', false];
    }

    public function testAsArray(): void
    {
        $actual = $this->service->asArray();
        self::assertEmpty($actual);
    }

    public function testAsHtml(): void
    {
        $actual = $this->service->asHtml();
        self::assertNotEmpty($actual);
    }

    public function testAsText(): void
    {
        $actual = $this->service->asText();
        self::assertNotEmpty($actual);
    }

    public function testGetLoadedExtensions(): void
    {
        $actual = $this->service->getLoadedExtensions();
        self::assertNotEmpty($actual);
    }

    public function testGetVersion(): void
    {
        $actual = $this->service->getVersion();
        self::assertSame(\PHP_VERSION, $actual);
    }

    #[DataProvider('getColors')]
    public function testIsColor(string $value, bool $expected): void
    {
        $actual = $this->service->isColor($value);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getDisabledValues')]
    public function testIsDisabled(string $value, bool $expected): void
    {
        $actual = $this->service->isDisabled($value);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getNoValues')]
    public function testIsNoValue(string $value, bool $expected): void
    {
        $actual = $this->service->isNoValue($value);
        self::assertSame($expected, $actual);
    }
}
