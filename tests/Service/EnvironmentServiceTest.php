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

use App\Enums\Environment;
use App\Service\EnvironmentService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EnvironmentServiceTest extends TestCase
{
    /**
     * @psalm-return \Generator<array-key, array{string, Environment}>
     */
    public static function getEnvironment(): \Generator
    {
        yield ['dev', Environment::DEVELOPMENT];
        yield ['prod', Environment::PRODUCTION];
        yield ['test', Environment::TEST];
    }

    /**
     * @psalm-return \Generator<array-key, array{string, bool}>
     */
    public static function getIsDevelopment(): \Generator
    {
        yield ['dev', true];
        yield ['prod', false];
        yield ['test', false];
    }

    /**
     * @psalm-return \Generator<array-key, array{string, bool}>
     */
    public static function getIsProduction(): \Generator
    {
        yield ['dev', false];
        yield ['prod', true];
        yield ['test', false];
    }

    /**
     * @psalm-return \Generator<array-key, array{string, bool}>
     */
    public static function getIsTest(): \Generator
    {
        yield ['dev', false];
        yield ['prod', false];
        yield ['test', true];
    }

    #[DataProvider('getEnvironment')]
    public function testGetEnvironment(string $env, Environment $expected): void
    {
        $service = new EnvironmentService($env);
        $actual = $service->getEnvironment();
        self::assertSame($expected, $actual);
    }

    public function testInvalidEnvironment(): void
    {
        self::expectException(\ValueError::class);
        new EnvironmentService('fake');
    }

    #[DataProvider('getIsDevelopment')]
    public function testIsDevelopment(string $env, bool $expected): void
    {
        $service = new EnvironmentService($env);
        $actual = $service->isDevelopment();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getIsProduction')]
    public function testIsProduction(string $env, bool $expected): void
    {
        $service = new EnvironmentService($env);
        $actual = $service->isProduction();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getIsTest')]
    public function testIsTest(string $env, bool $expected): void
    {
        $service = new EnvironmentService($env);
        $actual = $service->isTest();
        self::assertSame($expected, $actual);
    }
}
