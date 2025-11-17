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
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Exception\TypesException;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FixedFloatTypeTest extends TestCase
{
    /**
     * @throws Exception
     * @throws TypesException
     */
    #[\Override]
    public static function setUpBeforeClass(): void
    {
        if (!Type::hasType(FixedFloatType::NAME)) {
            Type::addType(FixedFloatType::NAME, FixedFloatType::class);
        }
    }

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
     * @throws TypesException
     * @throws ConversionException
     */
    #[DataProvider('getValues')]
    public function testConvertToDatabaseValue(mixed $value, float $expected): void
    {
        $platform = new MySQLPlatform();
        $type = $this->getFixedFloatType();
        $actual = $type->convertToDatabaseValue($value, $platform);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws ConversionException
     * @throws TypesException
     */
    #[DataProvider('getValues')]
    public function testConvertToPHPValue(mixed $value, float $expected): void
    {
        $platform = new MySQLPlatform();
        $type = $this->getFixedFloatType();
        $actual = $type->convertToPHPValue($value, $platform);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws TypesException
     */
    public function testName(): void
    {
        /** @phpstan-var FixedFloatType $type */
        $type = $this->getFixedFloatType();
        self::assertSame('fixed_float', $type->getName());
    }

    /**
     * @throws TypesException
     */
    public function testSQLDeclaration(): void
    {
        $platform = new MySQLPlatform();
        $type = $this->getFixedFloatType();
        $actual = $type->getSQLDeclaration([], $platform);
        $expected = "DOUBLE PRECISION DEFAULT '0'";
        self::assertSame($expected, $actual);
    }

    /**
     * @throws TypesException
     */
    private function getFixedFloatType(): Type
    {
        return Type::getType(FixedFloatType::NAME);
    }
}
