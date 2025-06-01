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

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Doctrine\TypedCollections\Rector\ClassMethod\NarrowArrayCollectionToCollectionRector;
use Rector\Doctrine\TypedCollections\Rector\If_\RemoveIsArrayOnCollectionRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\SingleMockPropertyTypeRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\TwigSetList;
use Rector\Symfony\Symfony73\Rector\Class_\InvokableCommandInputAttributeRector;
use Rector\TypeDeclaration\Rector\Class_\TypedPropertyFromCreateMockAssignRector;

return RectorConfig::configure()
    ->withCache(__DIR__ . '/var/cache/rector')
    ->withRootFiles()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/public',
    ])->withSkip([
        PreferPHPUnitThisCallRector::class,
        SingleMockPropertyTypeRector::class,
        TypedPropertyFromCreateMockAssignRector::class,
        NarrowArrayCollectionToCollectionRector::class,
        RemoveIsArrayOnCollectionRector::class,
        InvokableCommandInputAttributeRector::class,
    ])->withComposerBased(
        twig: true,
        doctrine: true,
        phpunit: true,
        symfony: true,
    )->withSets([
        // global
        SetList::PHP_82,
        SetList::CODE_QUALITY,
        SetList::INSTANCEOF,
        SetList::PRIVATIZATION,
        SetList::STRICT_BOOLEANS,
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
    ])->withAttributesSets(
        // annotations to attributes
        symfony: true,
        doctrine: true,
        phpunit: true,
    );
