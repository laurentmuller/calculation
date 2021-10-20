<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\Core\Configuration\Option;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // parameters
    $parameters = $containerConfigurator->parameters();

    // bootstrap file
    $parameters->set(Option::BOOTSTRAP_FILES, [
        __DIR__ . '/vendor/autoload.php'
    ]);

    // rules to skip
    $parameters->set(Option::SKIP, [
        ClosureToArrowFunctionRector::class,
        CallableThisArrayToAnonymousFunctionRector::class,
    ]);

    // rules to apply
    $containerConfigurator->import(SetList::PHP_74);
    $containerConfigurator->import(SetList::CODE_QUALITY);
};
