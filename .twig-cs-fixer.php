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

$config = new Config();
$config->allowNonFixableRules()
    ->addTokenParser(new SwitchTokenParser())
    ->getFinder()
    ->in('templates');

return $config;
