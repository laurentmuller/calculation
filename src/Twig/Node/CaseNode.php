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

namespace App\Twig\Node;

use Twig\Attribute\YieldReady;
use Twig\Node\Node;
use Twig\Node\Nodes;

/**
 * Class CaseNode.
 */
#[YieldReady]
class CaseNode extends Node
{
    /**
     * @param Node[] $values
     */
    public function __construct(array $values, Node $body)
    {
        parent::__construct([
            'values' => new Nodes($values),
            'body' => $body,
        ]);
    }
}
