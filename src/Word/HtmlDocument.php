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
        $this->addTitleStyle(1, ['size' => 20, 'bold' => true]);
        $this->addTitleStyle(2, ['size' => 18, 'bold' => true]);
        $this->addTitleStyle(3, ['size' => 16, 'bold' => true]);
        $this->addTitleStyle(4, ['size' => 14, 'bold' => true]);
        $this->addTitleStyle(5, ['size' => 12, 'bold' => true]);
        $this->addTitleStyle(6, ['size' => 10, 'bold' => true]);
    }

    private function getBootstrapStyles(): array
    {
        return \array_reduce(
            HtmlBootstrapColors::cases(),
            function (array $carry, HtmlBootstrapColors $color): array {
                $key = \sprintf('bg-%s', \strtolower($color->name));
                $value = \sprintf('background-color:%s;', $color->getPhpOfficeColor());
                $carry += [$key => $value];

                $key = \sprintf('text-%s', \strtolower($color->name));
                $value = \sprintf('color:%s;', $color->getPhpOfficeColor());

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
            'font-weight-bold' => 'font-weight:bold;',
            'font-italic' => 'font-style:italic;',
            'text-monospace' => 'font-family:Courier New;',
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
