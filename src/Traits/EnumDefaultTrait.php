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
 * Trait for enumeration implementing <code>EnumDefaultInterface</code> interface.
 *
 * @psalm-require-implements EnumDefaultInterface
 */
trait EnumDefaultTrait
{
    use EnumExtrasTrait;

    /**
     * Gets the default case enumeration.
     *
     * @throws \LogicException if no default case enumeration is found
     */
    public static function getDefault(): self
    {
        foreach (static::cases() as $value) {
            if ($value->isDefault()) {
                return $value;
            }
        }
        throw new \LogicException(\sprintf('No default value found for "%s" enumeration.', __CLASS__));
    }

    public function isDefault(): bool
    {
        return $this->getExtraBool(EnumDefaultInterface::NAME);
    }
}
