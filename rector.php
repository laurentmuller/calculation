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
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\Doctrine\Set\DoctrineSetList;

return static function (RectorConfig $rectorConfig): void {
    // bootstrap files
    $rectorConfig->bootstrapFiles([__DIR__ . '/vendor/autoload.php']);

    // paths
    $rectorConfig->paths([
        'src',
        'tests',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/src/Form/DataTransformer/AddressTransformer.php',
        __DIR__ . '/src/Form/DataTransformer/AbstractEntityTransformer.php',
    ]);

    // rules to apply
    $rectorConfig->sets([
        SetList::PHP_81,
        SetList::CODE_QUALITY,
        PHPUnitSetList::PHPUNIT_90,
        SymfonySetList::SYMFONY_60,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
    ]);
};
