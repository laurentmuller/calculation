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
 * Class implementing this interface deals with the indexed position in a collection.
 */
interface PositionInterface
{
    /**
     * Gets the position index in the collection.
     */
    public function getPosition(): int;

    /**
     * Sets the position index in the collection.
     */
    public function setPosition(int $position): static;
}
