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

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait for class implementing the <code></code>PositionInterface</code> interface.
 *
 * @psalm-require-implements \App\Interfaces\PositionInterface
 */
trait PositionTrait
{
    /**
     * The position index.
     */
    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    /**
     * Gets the position index in the collection.
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Sets the position index in the collection.
     */
    public function setPosition(int $position): static
    {
        if ($this->position !== $position) {
            $this->position = $position;
        }

        return $this;
    }
}
