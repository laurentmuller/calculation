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

use FriendsOfTwig\Twigcs\Config\Config;
use FriendsOfTwig\Twigcs\Config\ConfigInterface;
use FriendsOfTwig\Twigcs\Finder\TemplateFinder;

$finder = TemplateFinder::create()->in(__DIR__ . '/templates');

return Config::create()
    ->setDisplay(ConfigInterface::DISPLAY_BLOCKING)
    ->setSeverity('error')
    ->addFinder($finder);
