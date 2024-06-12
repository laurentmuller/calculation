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

namespace App\Tests\Enums;

use App\Enums\Environment;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Environment::class)]
class EnvironmentTest extends TestCase
{
    use TranslatorMockTrait;

    public static function getIsDevelopment(): \Iterator
    {
        yield [Environment::DEVELOPMENT, true];
        yield [Environment::PRODUCTION, false];
        yield [Environment::TEST, false];
    }

    public static function getIsProduction(): \Iterator
    {
        yield [Environment::DEVELOPMENT, false];
        yield [Environment::PRODUCTION, true];
        yield [Environment::TEST, false];
    }

    public static function getIsTest(): \Iterator
    {
        yield [Environment::DEVELOPMENT, false];
        yield [Environment::PRODUCTION, false];
        yield [Environment::TEST, true];
    }

    public static function getLabels(): \Iterator
    {
        yield [Environment::DEVELOPMENT, 'environment.dev'];
        yield [Environment::PRODUCTION, 'environment.prod'];
        yield [Environment::TEST, 'environment.test'];
    }

    public static function getValues(): \Iterator
    {
        yield [Environment::DEVELOPMENT, 'dev'];
        yield [Environment::PRODUCTION, 'prod'];
        yield [Environment::TEST, 'test'];
    }

    public function testCount(): void
    {
        $expected = 3;
        $actual = Environment::cases();
        self::assertCount($expected, $actual);
    }

    #[DataProvider('getIsDevelopment')]
    public function testIsDevelopment(Environment $environment, bool $expected): void
    {
        $actual = $environment->isDevelopment();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getIsProduction')]
    public function testIsProduction(Environment $environment, bool $expected): void
    {
        $actual = $environment->isProduction();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getIsTest')]
    public function testIsTest(Environment $environment, bool $expected): void
    {
        $actual = $environment->isTest();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLabels')]
    public function testLabel(Environment $environment, string $expected): void
    {
        $actual = $environment->getReadable();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLabels')]
    public function testTranslate(Environment $environment, string $expected): void
    {
        $translator = $this->createMockTranslator();
        $actual = $environment->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getValues')]
    public function testValue(Environment $environment, string $expected): void
    {
        $actual = $environment->value;
        self::assertSame($expected, $actual);
    }
}
