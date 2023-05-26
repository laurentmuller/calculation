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

namespace App\Twig;

use App\Service\CountryFlagService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Extension for Emoji country flags.
 *
 * @see CountryFlagService
 */
class EmojiFlagExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('flag_emoji', CountryFlagService::getFlag(...)),
        ];
    }
}
