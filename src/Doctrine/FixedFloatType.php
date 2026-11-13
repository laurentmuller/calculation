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

namespace App\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDbalType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Type that maps an SQL DOUBLE PRECISION to a PHP float with 2 decimals and 0.00 as the default value.
 */
#[AsDbalType(name: self::NAME)]
class FixedFloatType extends Type
{
    public const string NAME = 'fixed_float';

    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): float
    {
        return $this->convertToFloat($value);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): float
    {
        return $this->convertToFloat($value);
    }

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['scale'] = 2;
        $column['default'] = 0.0;
        $floatDeclaration = $platform->getFloatDeclarationSQL($column);
        $defaultDeclaration = $platform->getDefaultValueDeclarationSQL($column);

        return $floatDeclaration . $defaultDeclaration;
    }

    private function convertToFloat(mixed $value): float
    {
        return null === $value ? 0.0 : (float) $value;
    }
}
