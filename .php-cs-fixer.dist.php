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
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

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
    '@PHP82Migration:risky' => true,
    '@DoctrineAnnotation' => true,
    '@PHPUnit100Migration:risky' => true,

    // --------------------------------------------------------------
    //  Rules override
    // --------------------------------------------------------------
    'strict_param' => true,
    'php_unit_strict' => true,
    'no_useless_else' => true,
    'no_unused_imports' => true,
    'no_useless_return' => true,
    'strict_comparison' => true,
    'ordered_interfaces' => true,
    'final_internal_class' => true,
    'method_chaining_indentation' => true,
    'concat_space' => ['spacing' => 'one'],
    'list_syntax' => ['syntax' => 'short'],
    'array_syntax' => ['syntax' => 'short'],
    'ordered_class_elements' => ['sort_algorithm' => 'alpha'],
    'attribute_empty_parentheses' => ['use_parentheses' => false],
    'doctrine_annotation_array_assignment' => ['operator' => '='],
    'native_function_invocation' => ['include' => ['@internal', '@all', '@compiler_optimized']],
    'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
    'new_with_braces' => ['anonymous_class' => true, 'named_class' => true],
    'ordered_imports' => ['imports_order' => ['const', 'class', 'function']],
    'blank_line_before_statement' => ['statements' => ['declare', 'try', 'return']],
    'header_comment' => [
        'header' => $comment,
        'location' => 'after_open',
        'separate' => 'bottom'],
    'phpdoc_to_comment' => [
        'allow_before_return_statement' => true,
        'ignored_tags' => ['psalm-var', 'psalm-suppress', 'phpstan-var', 'phpstan-param', 'phpstan-ignore'],
    ],
];

$finder = Finder::create()
    ->in(__DIR__ . '/config')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->append([
        __FILE__,
        __DIR__ . '/public/index.php',
        __DIR__ . '/.twig-cs-fixer.php',
        __DIR__ . '/rector.php',
    ]);

$config = new Config();

return $config
    ->setCacheFile(__DIR__ . '/var/cache/php-cs-fixer/.php-cs-fixer.cache')
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setUnsupportedPhpVersionAllowed(false)
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules($rules);
