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
 *
 * Based on the rejected Twig pull request: https://github.com/twigphp/Twig/pull/185.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0
 */
#[YieldReady]
final class SwitchNode extends Node
{
    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this)
            ->write('switch (')
            ->subcompile($this->getNode('value'))
            ->raw(") {\n")
            ->indent();

        /** @psalm-var Node $case */
        foreach ($this->getNode('cases') as $case) {
            /** @psalm-var Node $value */
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
