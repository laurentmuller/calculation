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
        'text-left' => 'text-align: left;',
        'text-center' => 'text-align: center;',
        'text-right' => 'text-align: right;',
        'text-justify' => 'text-align: justify;',
        // font
        'font-italic' => 'font-style:italic;',
        'font-weight-bold' => 'font-weight:bold;',
        'text-monospace' => 'font-family:Courier New;',
    ];

    /**
     * The pattern to extract margins.
     */
    private const MARGINS_PATTERN = '/^[m|p]([tblrxy])?-(sm-|md-|lg-|xl-)?([012345])/im';

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
            'border-left' => ["border-left:$borderSolid"],
            'border-right' => ["border-right:$borderSolid"],

            'border-0' => ["border:$borderNone"],
            'border-top-0' => [
                "border-top:$borderNone",
                "border-bottom:$borderSolid",
                "border-left:$borderSolid",
                "border-right:$borderSolid",
            ],
            'border-left-0' => [
                "border-top:$borderSolid",
                "border-bottom:$borderSolid",
                "border-left:$borderNone",
                "border-right:$borderSolid",
            ],
            'border-right-0' => [
                "border-top:$borderSolid",
                "border-bottom:$borderSolid",
                "border-left:$borderSolid",
                "border-right:$borderNone",
            ],
            'border-bottom-0' => [
                "border-top:$borderSolid",
                "border-bottom:$borderNone",
                "border-left:$borderSolid",
                "border-right:$borderSolid",
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
                'l' => \sprintf('margin-left:%s;', $value),
                'r' => \sprintf('margin-right:%s;', $value),
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
