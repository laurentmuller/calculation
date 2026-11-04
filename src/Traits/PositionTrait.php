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
 * @phpstan-require-implements \App\Interfaces\PositionInterface
 */
trait PositionTrait
{
    /** The position index. */
    #[ORM\Column(options: ['default' => 0])]
    public int $position = 0;
}
