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
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\SingleMockPropertyTypeRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Set\TwigSetList;
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
    ])->withSets([
        // global
        SetList::PHP_82,
        SetList::CODE_QUALITY,
        SetList::PRIVATIZATION,
        SetList::INSTANCEOF,
        SetList::STRICT_BOOLEANS,
        SetList::TYPE_DECLARATION,
        // Doctrine
        DoctrineSetList::DOCTRINE_DBAL_40,
        DoctrineSetList::TYPED_COLLECTIONS,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
        DoctrineSetList::DOCTRINE_COLLECTION_22,
        // PHP-Unit
        PHPUnitSetList::PHPUNIT_110,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        // Symfony
        SymfonySetList::SYMFONY_72,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
        // twig
        TwigSetList::TWIG_24,
        TwigSetList::TWIG_UNDERSCORE_TO_NAMESPACE,
    ])->withAttributesSets(
        // annotations to attributes
        symfony: true,
        doctrine: true,
        phpunit: true
    );
