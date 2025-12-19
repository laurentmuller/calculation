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

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contains parameters to get a Mermaid diagram.
 */
class DiagramQuery
{
    public function __construct(
        public ?string $name = null,
        #[Assert\Range(min: 0.5, max: 2.0)]
        public ?float $zoom = null,
    ) {
    }
}
