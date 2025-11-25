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

use TwigCsFixer\Config\Config;
use TwigCsFixer\File\Finder;
use TwigCsFixer\Rules\Delimiter\EndBlockNameRule;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Standard\TwigCsFixer;

$cacheFile = __DIR__ . '/var/cache/twig-cs-fixer/.twig-cs-fixer.cache';

$ruleset = new Ruleset();
$ruleset->addStandard(new TwigCsFixer());
$ruleset->addRule(new EndBlockNameRule());

$finder = Finder::create()
    ->in('templates');

$config = new Config();
$config->allowNonFixableRules()
    ->setCacheFile($cacheFile)
    ->setRuleset($ruleset)
    ->setFinder($finder);

return $config;
