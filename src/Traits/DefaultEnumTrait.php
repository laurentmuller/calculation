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

/**
 * Trait for class implementing {@link DefaultEnumInterface} interface.
 *
 * @psalm-require-implements \App\Interfaces\DefaultEnumInterface
 */
trait DefaultEnumTrait
{
    use ExtendedExtrasTrait;

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
        return $this->getExtraBool('default');
    }
}
