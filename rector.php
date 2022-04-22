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
use Rector\Core\Configuration\Option;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    // bootstrap files
    $rectorConfig->bootstrapFiles([__DIR__ . '/vendor/autoload.php']);

    // paths
    $rectorConfig->paths([
        'src',
        'tests',
    ]);

    // rules to skip
    $rectorConfig->skip([AnnotationToAttributeRector::class]);

    // rules to apply
    $rectorConfig->sets([
        SetList::PHP_81,
        SetList::CODE_QUALITY,
        SymfonySetList::SYMFONY_54,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ]);
};
