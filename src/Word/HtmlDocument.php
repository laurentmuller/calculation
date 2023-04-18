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

use App\Controller\AbstractController;
use App\Pdf\Html\HtmlBootstrapColors;
use App\Utils\StringUtils;
use PhpOffice\PhpWord\Shared\Html;

/**
 * Document to output HTML content.
 */
class HtmlDocument extends AbstractWordDocument
{
    /**
     * The mapping between bootstrap class and style.
     */
    private array $styles = [];

    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent's controller
     * @param string             $content    the HTML content to render
     */
    public function __construct(AbstractController $controller, private readonly string $content)
    {
        parent::__construct($controller);
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        if ('' === $this->content) {
            return false;
        }

        $this->addHeaderStyles();
        $content = $this->parseContent();
        $section = $this->createDefaultSection();
        Html::addHtml($section, $content, false, false);

        return true;
    }

    private function addHeaderStyles(): void
    {
        $fontStyle = ['bold' => true];
        $paragraphStyle = ['keepNext' => true]; // , 'keepLines' => true];
        foreach (\range(1, 6) as $index) {
            $fontStyle['size'] = 20 - 2 * ($index - 1);
            $this->addTitleStyle($index, $fontStyle, $paragraphStyle);
        }
    }

    private function getBootstrapStyles(): array
    {
        return \array_reduce(
            HtmlBootstrapColors::cases(),
            function (array $carry, HtmlBootstrapColors $color): array {
                $name = \strtolower($color->name);
                $officeColor = $color->getPhpOfficeColor();

                // background
                $key = \sprintf('bg-%s', $name);
                $value = \sprintf('background-color:%s;', $officeColor);
                $carry += [$key => $value];

                // color
                $key = \sprintf('text-%s', $name);
                $value = \sprintf('color:%s;', $officeColor);

                return $carry + [$key => $value];
            },
            []
        );
    }

    private function getClassStyles(): array
    {
        return [
            // margin (must be replaced by regex)
            'mb-0' => 'margin-bottom:0;',
            'mb-2' => 'margin-bottom:0.5em;',
            // alignment
            'text-left' => 'text-align: left;',
            'text-center' => 'text-align: center;',
            'text-right' => 'text-align: right;',
            'text-justify' => 'text-align: justify;',
            // font
            'font-italic' => 'font-style:italic;',
            'font-weight-bold' => 'font-weight:bold;',
            'text-monospace' => 'font-family:Courier New;',
            // border
            'border' => 'border: 1px solid #dee2e6',
            'border-top' => 'border-top: 1px solid #dee2e6',
            'border-bottom' => 'border-bottom: 1px solid #dee2e6',
            'border-left' => 'border-left: 1px solid #dee2e6',
            'border-right' => 'border-right: 1px solid #dee2e6',
            // tag
            'class' => 'style',
        ];
    }

    private function getStyles(): array
    {
        if ([] === $this->styles) {
            $this->styles = \array_merge(
                $this->getClassStyles(),
                $this->getBootstrapStyles()
            );
        }

        return $this->styles;
    }

    private function parseContent(): string
    {
        /** @psalm-var array<string, string> $styles */
        $styles = $this->getStyles();
        $content = StringUtils::replace($styles, $this->content);

        return \preg_replace('/\s+/', ' ', $content);
    }
}
