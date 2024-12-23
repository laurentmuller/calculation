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

namespace App\Tests\Types;

use App\Types\FixedFloatType;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FixedFloatTypeTest extends TestCase
{
    public static function getValues(): \Generator
    {
        yield [null, 0.0];
        yield [0.0, 0.0];
        yield [0, 0.0];
        yield [10, 10.0];
        yield [-10, -10.0];
        yield ['a', 0.0];
        yield ['10', 10.0];
    }

    /**
     * @throws ConversionException
     *
     * @psalm-suppress InternalMethod
     */
    #[DataProvider('getValues')]
    public function testConvertToDatabaseValue(mixed $value, float $expected): void
    {
        $platform = new MySQLPlatform();
        $type = new FixedFloatType();
        $actual = $type->convertToDatabaseValue($value, $platform);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws ConversionException
     *
     * @psalm-suppress InternalMethod
     */
    #[DataProvider('getValues')]
    public function testConvertToPHPValue(mixed $value, float $expected): void
    {
        $platform = new MySQLPlatform();
        $type = new FixedFloatType();
        $actual = $type->convertToPHPValue($value, $platform);
        self::assertSame($expected, $actual);
    }

    /**
     * @psalm-suppress InternalMethod
     */
    public function testName(): void
    {
        $type = new FixedFloatType();
        self::assertSame('fixed_float', $type->getName());
    }

    /**
     * @psalm-suppress InternalMethod
     */
    public function testSQLDeclaration(): void
    {
        $platform = new MySQLPlatform();
        $type = new FixedFloatType();
        $actual = $type->getSQLDeclaration([], $platform);
        $expected = "DOUBLE PRECISION DEFAULT '0'";
        self::assertSame($expected, $actual);
    }
}
