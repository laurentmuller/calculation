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

use App\Interfaces\EntityInterface;

/**
 * Contains parameter meta-data.
 */
readonly class MetaData
{
    /**
     * @param string $name     the parameter name
     * @param string $property the property name
     * @param string $type     the property type
     * @param mixed  $default  the default value
     */
    public function __construct(
        public string $name,
        public string $property,
        public string $type,
        public mixed $default
    ) {
    }

    /**
     * @psalm-assert-if-true ?\BackedEnum $this->default
     * @psalm-assert-if-true class-string<\BackedEnum> $this->type
     */
    public function isBackedEnumType(): bool
    {
        return \is_a($this->type, \BackedEnum::class, true);
    }

    /**
     * @psalm-assert-if-true class-string<EntityInterface> $this->type
     */
    public function isEntityInterfaceType(): bool
    {
        return \is_a($this->type, EntityInterface::class, true);
    }
}
