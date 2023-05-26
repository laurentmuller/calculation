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

namespace App\Traits;

use App\Interfaces\EnumDefaultInterface;

/**
 * Trait for enumeration implementing {@link EnumDefaultInterface} interface.
 *
 * @psalm-require-implements EnumDefaultInterface
 */
trait EnumDefaultTrait
{
    use EnumExtrasTrait;

    public static function getDefault(): self
    {
        /** @var self[] $values */
        $values = static::cases();
        foreach ($values as $value) {
            if ($value->isDefault()) {
                return $value;
            }
        }

        throw new \LogicException('Unable to find the default value.');
    }

    public function isDefault(): bool
    {
        return $this->getExtraBool(EnumDefaultInterface::NAME);
    }
}
