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
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class SwitchNode.
 */
#[YieldReady]
final class SwitchNode extends Node
{
    /**
     * @param Node[] $nodes
     */
    public function __construct(array $nodes, int $lineno)
    {
        parent::__construct(nodes: $nodes, lineno: $lineno);
    }

    #[\Override]
    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this)
            ->write('switch (')
            ->subcompile($this->getNode('value'))
            ->raw(") {\n")
            ->indent();

        foreach ($this->getNode('cases') as $case) {
            foreach ($case->getNode('values') as $value) {
                $compiler->write('case ')
                    ->subcompile($value)
                    ->raw(":\n");
            }
            $compiler->write("{\n")
                ->indent()
                ->subcompile($case->getNode('body'))
                ->write("break;\n")
                ->outdent()
                ->write("}\n");
        }
        if ($this->hasNode('default')) {
            $compiler->write("default:\n")
                ->write("{\n")
                ->indent()
                ->subcompile($this->getNode('default'))
                ->outdent()
                ->write("}\n");
        }
        $compiler->outdent()
            ->write("}\n");
    }
}
