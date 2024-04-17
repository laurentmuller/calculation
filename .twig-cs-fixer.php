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

use App\Twig\TokenParser\SwitchTokenParser;
use TwigCsFixer\Config\Config;
use TwigCsFixer\File\Finder;
use TwigCsFixer\Rules\Operator\OperatorSpacingRule;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Standard\TwigCsFixer;

$ruleset = new Ruleset();
$ruleset->addStandard(new TwigCsFixer());
$ruleset->removeRule(OperatorSpacingRule::class);

$finder = Finder::create()
    ->in(__DIR__ . '/templates');

$config = new Config();
$config->addTokenParser(new SwitchTokenParser())
    ->setRuleset($ruleset)
    ->setFinder($finder);

return $config;
