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

use App\Interfaces\DefaultEnumInterface;
use Elao\Enum\ExtrasTrait;

/**
 * Trait for class implementing {@link DefaultEnumInterface}.
 *
 * @psalm-require-implements \App\Interfaces\DefaultEnumInterface
 */
trait DefaultEnumTrait
{
    use ExtrasTrait;

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function isDefault(): bool
    {
        /** @psalm-var bool|null $default */
        $default = $this->getExtra('default');

        return \is_bool($default) && $default;
    }
}
