<?php
declare(strict_types = 1);

use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\Core\Configuration\Option;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::PATHS, [
       __DIR__ . '/src'
    ]);

    $parameters->set(Option::AUTOLOAD_PATHS, [
        __DIR__ . '/vendor',
    ]);

    // Define what rule sets will be applied
    // $container Configurator import (SetList::CODE_QUALITY)

    // get services (needed for register a single rule)
    $services = $containerConfigurator->services();

    // register a single rule
    $services->set(ExplicitBoolCompareRector::class);
};
