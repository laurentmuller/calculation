<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) Laurent Muller <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$comment = <<<COMMENT
This file is part of the Calculation package.

(c) bibi.nu. <bibi@bibi.nu>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
COMMENT;

$rules = [

    //--------------------------------------------------------------
    //  Rulesets
    //--------------------------------------------------------------

    '@Symfony'                  => true,
    '@Symfony:risky'            => true,

    '@PHP70Migration'           => true,
    '@PHP71Migration:risky'     => true,
    '@PHP73Migration'           => true,

    '@DoctrineAnnotation'       => true,

    '@PHPUnit75Migration:risky' => true,

    //--------------------------------------------------------------
    //  Rules override
    //--------------------------------------------------------------

    'method_chaining_indentation' => true,
    'native_function_invocation'  => ['include' => ['@internal', 'all']],
    'final_internal_class'        => true,
    'header_comment'              => ['header' => $comment, 'location' => 'after_open', 'separate' => 'bottom'],
    'blank_line_before_statement' => ['statements' => ['declare', 'try', 'return']],
    'no_unused_imports'           => true,
    'strict_comparison'           => true,
    'strict_param'                => true,
    'ordered_imports'             => true,
    'ordered_class_elements'      => ['sort_algorithm' => 'alpha'],
    'concat_space'                => ['spacing' => 'one'],
    'array_syntax'                => ['syntax' => 'short'],
    'list_syntax'                 => ['syntax' => 'short'],
    'doctrine_annotation_array_assignment' => ['operator' => '='],
];

$finder = PhpCsFixer\Finder::create()
    ->in(realpath(__DIR__ . '/src'))
    ->in(realpath(__DIR__ . '/tests'));

$config = new PhpCsFixer\Config();
return $config
    ->setRiskyAllowed(true)
    ->setUsingCache(false)
    ->setFinder($finder)
    ->setRules($rules);
