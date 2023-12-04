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

namespace App\Interfaces;

/**
 * Class implementing this interface deals with the parent's entity.
 *
 * @template T of EntityInterface
 */
interface ParentEntityInterface
{
    /**
     * Gets the parent's entity or null if none.
     *
     * @psalm-return T|null
     */
    public function getParentEntity(): ?EntityInterface;
}
