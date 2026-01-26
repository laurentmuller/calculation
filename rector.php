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

use Rector\CodingStyle\Rector\ArrowFunction\StaticArrowFunctionRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\Closure\StaticClosureRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveParentDelegatingConstructorRector;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\SingleMockPropertyTypeRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\TwigSetList;

$paths = [
    __DIR__ . '/config',
    __DIR__ . '/src',
    __DIR__ . '/tests',
    __DIR__ . '/public',
];

$skip = [
    // allow self::functions for PHP unit
    PreferPHPUnitThisCallRector::class,
    // allow both object and mock classes
    SingleMockPropertyTypeRector::class,
    // not convert class-string to class
    StringClassNameToClassConstantRector::class => [
        __DIR__ . '/tests/Traits/CheckSubClassTraitTest.php',
    ],
    // not update method visibilities
    MakeInheritedMethodVisibilitySameAsParentRector::class => [
        __DIR__ . '/tests/Fixture/*.php',
    ],
    // no space before or after statements
    NewlineAfterStatementRector::class,
    NewlineBeforeNewAssignSetRector::class,
    // don't separate constants
    NewlineBetweenClassLikeStmtsRector::class,
    // don't rename exception
    CatchExceptionNameMatchingTypeRector::class,
    // allow delegate constructor
    RemoveParentDelegatingConstructorRector::class,
];

$sets = [
    // global
    SetList::PHP_83,
    SetList::CODE_QUALITY,
    SetList::CODING_STYLE,
    SetList::DEAD_CODE,
    SetList::INSTANCEOF,
    SetList::PRIVATIZATION,
    SetList::TYPE_DECLARATION,
    // Doctrine
    DoctrineSetList::DOCTRINE_CODE_QUALITY,
    DoctrineSetList::TYPED_COLLECTIONS,
    // PHP-Unit
    PHPUnitSetList::PHPUNIT_110,
    PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    // twig
    TwigSetList::TWIG_24,
    TwigSetList::TWIG_UNDERSCORE_TO_NAMESPACE,
];

$rules = [
    // static closure and arrow functions
    StaticClosureRector::class,
    StaticArrowFunctionRector::class,
];

return RectorConfig::configure()
    ->withCache(__DIR__ . '/var/cache/rector')
    ->withRootFiles()
    ->withPaths($paths)
    ->withSkip($skip)
    ->withSets($sets)
    ->withRules($rules)
    ->withComposerBased(
        twig: true,
        doctrine: true,
        phpunit: true,
        symfony: true,
    )->withPhpSets(
        php83: true
    )->withAttributesSets(
        symfony: true,
        doctrine: true,
        phpunit: true,
    );
