<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // parameters
    $parameters = $containerConfigurator->parameters();

    // bootstrap file
    $parameters->set(Option::BOOTSTRAP_FILES, [
        __DIR__ . '/vendor/autoload.php'],
    );

    // rules to skip
    $parameters->set(Option::SKIP, [
        AnnotationToAttributeRector::class,
    ]);

    // rules to apply
    $containerConfigurator->import(SetList::PHP_80);
    $containerConfigurator->import(SetList::CODE_QUALITY);
    $containerConfigurator->import(SymfonySetList::SYMFONY_54);
    $containerConfigurator->import(SymfonySetList::SYMFONY_CODE_QUALITY);
    $containerConfigurator->import(SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION);
};
