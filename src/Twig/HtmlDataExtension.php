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

use Twig\Attribute\AsTwigFunction;
use Twig\Environment;
use Twig\Extra\Html\HtmlExtension;

/**
 * Twig extension to render data attributes.
 */
class HtmlDataExtension
{
    #[AsTwigFunction(name: 'html_data_attr', needsEnvironment: true, isSafe: ['html'])]
    public static function htmlDataAttributes(Environment $env, iterable|string|false|null ...$args): string
    {
        $attr = [];
        /** @var array<string, mixed> $args */
        $args = HtmlExtension::htmlAttrMerge(...$args);
        foreach ($args as $name => $value) {
            if (\is_bool($value)) {
                $value = \json_encode($value);
            }
            if (!\str_starts_with($name, 'data-')) {
                $name = 'data-' . $name;
            }
            $attr[$name] = $value;
        }

        return HtmlExtension::htmlAttr($env, $attr);
    }
}
