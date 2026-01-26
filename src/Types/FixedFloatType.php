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

namespace App\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Type that maps an SQL DOUBLE to a PHP float with 2 decimals and 0.00 as the default value.
 */
class FixedFloatType extends Type
{
    final public const string NAME = 'fixed_float';

    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): float
    {
        return $this->convertToFloat($value);
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): float
    {
        return $this->convertToFloat($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['scale'] = 2;
        $declaration = $platform->getFloatDeclarationSQL($column);

        return \sprintf("%s DEFAULT '0'", $declaration);
    }

    private function convertToFloat(mixed $value): float
    {
        return null === $value ? 0.0 : (float) $value;
    }
}
