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

use App\Util\StringUtils;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension to export and highlight expressions.
 */
class HighlightExtension extends AbstractExtension
{
    private const HIGH_LIGHT_REGEX = [
        // variable
        '/(\')(\w+)(\')( =>)/' => '$1<samp class="variable">$2</samp>$3$4',
        // number
        '/(=> )([+-]?([0-9]*[.])?[0-9]+)/' => '$1<samp class="number">$2</samp>',
        '/([+-]?([0-9]*[.])?[0-9]+)( =>)/' => '<samp class="number">$1</samp>$2',
        // string
        '/(=> \')(.*)(\')/' => '$1<samp class="string">$2</samp>$3',
    ];

    private const HIGH_LIGHT_STYLE = [
        '[' => '<samp class="other">[</samp>',
        ']' => '<samp class="other">]</samp>',
        ',' => '<samp class="other">,</samp>',
        '=>' => '<samp class="other">=></samp>',
        '\'' => '<samp class="other">\'</samp>',
    ];

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('var_export', StringUtils::exportVar(...)),
            new TwigFilter('var_export_html', $this->exportVarHtml(...), ['is_safe' => ['html']]),
        ];
    }

    private function exportVarHtml(mixed $expression): string
    {
        $result = StringUtils::exportVar($expression);
        $result = \preg_replace(\array_keys(self::HIGH_LIGHT_REGEX), \array_values(self::HIGH_LIGHT_REGEX), $result);
        $result = \strtr($result, self::HIGH_LIGHT_STYLE);

        return \trim($result);
    }
}
