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

namespace App\Word;

use App\Pdf\Html\HtmlBootstrapColors;
use App\Utils\StringUtils;

/**
 * Class to replace class attributes by style attributes.
 */
class HtmlWordParser
{
    /**
     * The pattern to extract classes.
     */
    private const CLASS_PATTERN = '/(class=")([^\"]*)\"/mi';

    /**
     * The map between class name and style.
     */
    private const CLASSES_TO_STYLES = [
        // alignment
        'text-start' => 'text-align:left;',
        'text-center' => 'text-align:center;',
        'text-end' => 'text-align:right;',
        'text-justify' => 'text-align:justify;',
        // font
        'fw-bold' => 'font-weight:bold;',
        'fst-italic' => 'font-style:italic;',
        'font-monospace' => 'font-family:Courier New;',
    ];

    /**
     * The pattern to extract margins.
     */
    private const MARGINS_PATTERN = '/^[m|p]([tbsexy])?-(sm-|md-|lg-|xl-|xxl-)?([012345])/im';

    /**
     * The mapping between class name and style.
     *
     * @var array<string, string>
     */
    private array $styles = [];

    /**
     * Parse the given content by replacing class attributes by style attributes.
     */
    public function parse(string $content): string
    {
        // trim spaces
        $content = \preg_replace('/\s+/', ' ', $content);

        // replace classes by styles
        return \preg_replace_callback(
            self::CLASS_PATTERN,
            fn (array $matches): string => \sprintf('style="%s"', $this->mapClassName($matches[2])),
            $content,
            \PREG_OFFSET_CAPTURE
        );
    }

    /**
     * @psalm-return array<string, string>
     */
    private function getBootstrapStyles(): array
    {
        /** @psalm-var array<string, string> $result */
        $result = \array_reduce(
            HtmlBootstrapColors::cases(),
            function (array $carry, HtmlBootstrapColors $color): array {
                $name = \strtolower($color->name);
                $value = $color->value;

                return $carry + [
                    "text-$name" => "color:$value;",
                    "border-$name" => "border-color:$value;",
                    "bg-$name" => "background-color:$value;",
                    "text-bg-$name" => "background-color:$value;",
                ];
            },
            []
        );

        return $result;
    }

    /**
     * @psalm-return array<string, string>
     */
    private function getStyles(): array
    {
        if ([] === $this->styles) {
            $this->styles = \array_merge(
                self::CLASSES_TO_STYLES,
                $this->getBootstrapStyles()
            );
        }

        return $this->styles;
    }

    private function mapClassName(string $className): string
    {
        if ('' === $className) {
            return '';
        }
        $names = $this->splitClassName($className);
        if ([] === $names) {
            return '';
        }
        $names = \array_map($this->replaceClass(...), $names);

        return \implode('', $names);
    }

    /**
     * Parses the border class.
     */
    private function parseBorders(string $class): string
    {
        $borderNone = '0 #000000 none;';
        $borderSolid = '1px #808080 solid;';
        $border = match ($class) {
            'border' => ["border:$borderSolid"],
            'border-top' => ["border-top:$borderSolid"],
            'border-bottom' => ["border-bottom:$borderSolid"],
            'border-start' => ["border-left:$borderSolid"],
            'border-end' => ["border-right:$borderSolid"],

            'border-0' => ["border:$borderNone"],
            'border-top-0' => [
                "border-top:$borderNone",
                "border-bottom:$borderSolid",
                "border-start:$borderSolid",
                "border-end:$borderSolid",
            ],
            'border-left-0' => [
                "border-top:$borderSolid",
                "border-bottom:$borderSolid",
                "border-start:$borderNone",
                "border-end:$borderSolid",
            ],
            'border-right-0' => [
                "border-top:$borderSolid",
                "border-bottom:$borderSolid",
                "border-start:$borderSolid",
                "border-end:$borderNone",
            ],
            'border-bottom-0' => [
                "border-top:$borderSolid",
                "border-bottom:$borderNone",
                "border-start:$borderSolid",
                "border-end:$borderSolid",
            ],
            default => []
        };
        if ([] !== $border) {
            return \implode('', $border);
        }

        return $class;
    }

    /**
     * Parses the margins class.
     */
    private function parseMargins(string $class): string
    {
        $matches = [];
        if (\preg_match_all(self::MARGINS_PATTERN, $class, $matches, \PREG_SET_ORDER)) {
            $value = match ((int) $matches[0][3]) {
                1 => '4px',     // 0.25rem
                2 => '8px',     // 0.5rem
                3 => '16px',    // 1.0rem
                4 => '24px',    // 1.5rem
                5 => '48px',    // 3.0rem
                default => '0',
            };

            return match ($matches[0][1]) {
                't' => \sprintf('margin-top:%s;', $value),
                'b' => \sprintf('margin-bottom:%s;', $value),
                's' => \sprintf('margin-left:%s;', $value),
                'e' => \sprintf('margin-right:%s;', $value),
                'x' => \sprintf('margin-left:%1$s;margin-right:%1$s;', $value),
                'y' => \sprintf('margin-top:%1$s;margin-bottom:%1$s;', $value),
                default => \sprintf('margin:%s;', $value),
            };
        }

        return $class;
    }

    private function replaceClass(string $class): string
    {
        $class = $this->parseBorders($class);
        $class = $this->parseMargins($class);

        return StringUtils::replace($this->getStyles(), $class);
    }

    /**
     * @return string[]
     */
    private function splitClassName(string $className): array
    {
        return \array_unique(\array_filter(\explode(' ', \strtolower($className))));
    }
}
