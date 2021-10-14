<?php
declare(strict_types = 1);

use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Set\ValueObject\DowngradeSetList;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\PHPStanRules\Rules\Explicit\ExplicitMethodCallOverMagicGetSetRector;
use Symplify\PHPStanRules\Rules\Explicit\ExplicitMethodCallOverMagicGetSetRule;

return static function (ContainerConfigurator $containerConfigurator): void {
    // parameters
    $parameters = $containerConfigurator->parameters();

    // bootstrap files
    $parameters->set(Option::BOOTSTRAP_FILES, [
        __DIR__ . '/vendor/autoload.php'
    ]);

    // rules to skip
    $parameters->set(Option::SKIP,
        [
            ClosureToArrowFunctionRector::class,
            FlipTypeControlToUseExclusiveTypeRector::class,
            CallableThisArrayToAnonymousFunctionRector::class,
            ExplicitMethodCallOverMagicGetSetRector::class,
            ExplicitMethodCallOverMagicGetSetRule::class,
        ]);

    // rules to apply
    $containerConfigurator->import(SetList::PHP_74);
    $containerConfigurator->import(SetList::CODE_QUALITY);
};
