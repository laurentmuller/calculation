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

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$comment = <<<COMMENT
    This file is part of the Calculation package.

    (c) bibi.nu <bibi@bibi.nu>

    For the full copyright and license information, please view the LICENSE
    file that was distributed with this source code.
    COMMENT;

$rules = [
    // --------------------------------------------------------------
    //  Rule sets
    // --------------------------------------------------------------
    '@Symfony' => true,
    '@Symfony:risky' => true,
    '@PHP82Migration' => true,
    '@PHP80Migration:risky' => true,
    '@DoctrineAnnotation' => true,
    '@PHPUnit100Migration:risky' => true,

    // --------------------------------------------------------------
    //  Rules override
    // --------------------------------------------------------------
    'method_chaining_indentation' => true,
    'native_function_invocation' => ['include' => ['@internal', 'all']],
    'final_internal_class' => true,
    'header_comment' => ['header' => $comment, 'location' => 'after_open', 'separate' => 'bottom'],
    'blank_line_before_statement' => ['statements' => ['declare', 'try', 'return']],
    'no_unused_imports' => true,
    'strict_comparison' => true,
    'strict_param' => true,
    'ordered_imports' => ['imports_order' => ['const', 'class', 'function']],
    'ordered_class_elements' => ['sort_algorithm' => 'alpha'],
    'concat_space' => ['spacing' => 'one'],
    'array_syntax' => ['syntax' => 'short'],
    'list_syntax' => ['syntax' => 'short'],
    'doctrine_annotation_array_assignment' => ['operator' => '='],
    'ordered_interfaces' => true,
    'no_useless_else' => true,
    'no_useless_return' => true,
    'php_unit_strict' => true,
    'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
];

$finder = Finder::create()
    ->in(__DIR__ . '/config')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

$config = new Config();

return $config
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules($rules);
