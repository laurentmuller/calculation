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

use App\Controller\ThemeController;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for theme functions.
 */
class ThemeExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('theme_dark', $this->isDarkTheme(...)),
        ];
    }

    /**
     * Returns if the selected theme is dark.
     */
    private function isDarkTheme(Request $request): bool
    {
        return $request->cookies->getBoolean(ThemeController::KEY_DARK);
    }
}
