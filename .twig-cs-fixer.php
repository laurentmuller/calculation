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

$cache_dir = __DIR__ . '/var/cache/twig-cs-fixer/';
if (!file_exists($cache_dir)) {
    mkdir($cache_dir, 0777, true);
}

$config = new Config();
$config->allowNonFixableRules()
    ->setCacheFile($cache_dir . '/.twig-cs-fixer.cache')
    ->getFinder()
    ->in('templates');

return $config;
