<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Twig\Node;

use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class SwitchNode
 * Based on the rejected Twig pull request: https://github.com/fabpot/Twig/pull/185.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0
 */
final class SwitchNode extends Node
{
    /**
     * {@inheritdoc}
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write('switch (')
            ->subcompile($this->getNode('value'))
            ->raw(") {\n")
            ->indent();

        foreach ($this->getNode('cases') as $case) {
            // The 'body' node may have been removed by Twig if it was an empty text node in a sub-template,
            // outside of any blocks
            /** @var Node $case */
            if (!$case->hasNode('body')) {
                continue;
            }

            foreach ($case->getNode('values') as $value) {
                $compiler
                    ->write('case ')
                    ->subcompile($value)
                    ->raw(":\n");
            }

            $compiler
                ->write("{\n")
                ->indent()
                ->subcompile($case->getNode('body'))
                ->write("break;\n")
                ->outdent()
                ->write("}\n");
        }

        if ($this->hasNode('default')) {
            $compiler
                ->write("default:\n")
                ->write("{\n")
                ->indent()
                ->subcompile($this->getNode('default'))
                ->outdent()
                ->write("}\n");
        }

        $compiler
            ->outdent()
            ->write("}\n");
    }
}
