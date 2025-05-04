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

use App\Pdf\Html\HtmlBootstrapColor;
use App\Pdf\Html\HtmlSpacing;
use App\Traits\ArrayTrait;
use App\Utils\StringUtils;

/**
 * Class to replace class attributes by style attributes.
 */
class HtmlWordParser
{
    use ArrayTrait;

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
        // font style
        'fw-bold' => 'font-weight:bold;',
        'fst-italic' => 'font-style:italic;',
        'font-monospace' => 'font-family:Courier New;',
        // page-break
        'page-break' => 'page-break-after:always;',
    ];

    /**
     * The mapping between class name and style.
     *
     * @var array<string, string>
     */
    private array $styles = [];

    /**
     * Parse the given content by replacing class attributes with style attributes.
     */
    public function parse(string $content): string
    {
        // trim spaces
        $content = StringUtils::pregReplace('/\s+/', ' ', $content);

        if ('' === $content) {
            return $content;
        }

        // replace classes by styles
        return (string) \preg_replace_callback(
            self::CLASS_PATTERN,
            fn (array $matches): string => \sprintf('style="%s"', $this->mapClassName($matches[2])),
            $content,
            \PREG_OFFSET_CAPTURE
        );
    }

    /**
     * @return array<string, string>
     */
    private function getBootstrapStyles(): array
    {
        return \array_reduce(
            HtmlBootstrapColor::cases(),
            /** @psalm-param array<string, string> $carry  */
            function (array $carry, HtmlBootstrapColor $color): array {
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
    }

    /**
     * @return array<string, string>
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
            'border-start-0' => [
                "border-top:$borderSolid",
                "border-bottom:$borderSolid",
                "border-left:$borderNone",
                "border-right:$borderSolid",
            ],
            'border-end-0' => [
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
     * Parses margins class.
     */
    private function parseMargins(string $class): string
    {
        $spacing = HtmlSpacing::instance($class);
        if (!$spacing instanceof HtmlSpacing || $spacing->isNone()) {
            return $class;
        }

        $size = match ($spacing->size) {
            1 => '4px',     // 0.25rem
            2 => '8px',     // 0.5rem
            3 => '16px',    // 1.0rem
            4 => '24px',    // 1.5rem
            5 => '48px',    // 3.0rem
            default => '0',
        };

        if ($spacing->isAll()) {
            return \sprintf('margin:%s;', $size);
        }

        $result = '';
        if ($spacing->top) {
            $result .= \sprintf('margin-top:%s;', $size);
        }
        if ($spacing->bottom) {
            $result .= \sprintf('margin-bottom:%s;', $size);
        }
        if ($spacing->left) {
            $result .= \sprintf('margin-left:%s;', $size);
        }
        if ($spacing->right) {
            $result .= \sprintf('margin-right:%s;', $size);
        }

        return $result;
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
        return $this->getUniqueFiltered(\explode(' ', \strtolower($className)));
    }
}
