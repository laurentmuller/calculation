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

namespace App\Parameter;

use App\Attribute\Parameter;

/**
 * Contains parameter meta-data.
 *
 * @phpstan-import-type TValue from Parameter
 */
readonly class MetaData
{
    /**
     * template T of \BackedEnum.
     *
     * @param string                         $name     the parameter name
     * @param string                         $property the property name
     * @param PropertyType                   $type     the property type
     * @param TValue                         $default  the default value
     * @param class-string<\BackedEnum>|null $enum     the backed enum class
     */
    public function __construct(
        public string $name,
        public string $property,
        public PropertyType $type,
        public mixed $default,
        private ?string $enum = null,
    ) {
    }

    /**
     * @return class-string<\BackedEnum>
     */
    public function getEnum(): string
    {
        /** @phpstan-var class-string<\BackedEnum> */
        return (string) $this->enum;
    }
}
