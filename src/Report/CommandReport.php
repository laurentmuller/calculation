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

namespace App\Report;

use App\Controller\AbstractController;
use App\Pdf\PdfFont;
use App\Pdf\PdfStyle;
use App\Service\CommandService;
use App\Utils\StringUtils;
use fpdf\PdfFontName;
use fpdf\PdfMove;
use fpdf\PdfTextAlignment;

/**
 * Report for application commands.
 *
 * @psalm-import-type CommandType from CommandService
 * @psalm-import-type ArgumentType from CommandService
 * @psalm-import-type OptionType from CommandService
 *
 * @extends AbstractArrayReport<CommandType>
 */
class CommandReport extends AbstractArrayReport
{
    private const FIXED_WIDTH = 46.0;

    /**
     * @psalm-param CommandType[] $entities
     */
    public function __construct(AbstractController $controller, array $entities)
    {
        parent::__construct($controller, $entities);
        $this->setTitleTrans('admin.commands.title');
    }

    protected function doRender(array $entities): bool
    {
        $this->setCellMargin(0.0);
        foreach ($entities as $entity) {
            $this->addPage();
            $name = $entity['name'];
            $this->addBookmark($name, currentY: false);
            $this->outputName($name);
            $this->outputDescription($entity['description']);
            $this->outputUsage($entity['usage']);
            $this->outputArguments($entity['definition']['arguments']);
            $this->outputOptions($entity['definition']['options']);
            $this->outputHelp($entity['help']);
        }
        $this->addPageIndex();

        return true;
    }

    private function applyFixedStyle(float $size = 7.5): void
    {
        PdfStyle::default()->setFontName(PdfFontName::COURIER)->setFontSize($size)->apply($this);
    }

    private function buildOption(string $name, string $shortcut): string
    {
        return '' === $shortcut ? '    ' . $name : \sprintf('%s, %s', $shortcut, $name);
    }

    private function cellIndent(): void
    {
        $this->cell(2.0);
    }

    private function encodeDescription(string $description, string $display): string
    {
        $html = $this->replaceHtml($description);
        if ('[]' === $display) {
            return \sprintf('%s (multiple values allowed)', $html);
        }

        if ('' !== $display) {
            return \sprintf('%s  [default: %s]', $html, $display);
        }

        return $html;
    }

    private function fixedCell(string $text): void
    {
        $this->applyFixedStyle();
        $this->cell(self::FIXED_WIDTH, text: $text);
        $this->resetStyle();
    }

    /**
     * @psalm-param array<string, ArgumentType> $arguments
     */
    private function outputArguments(array $arguments): void
    {
        if ([] === $arguments) {
            return;
        }

        $this->outputHeader('Arguments:');
        foreach ($arguments as $argument) {
            $this->cellIndent();
            $this->fixedCell($argument['name']);
            $this->multiCell(
                text: $this->encodeDescription($argument['description'], $argument['display']),
                align: PdfTextAlignment::LEFT
            );
        }
        $this->lineBreak(1.0);
    }

    private function outputDescription(string $description): void
    {
        if (!StringUtils::isString($description)) {
            return;
        }
        $this->outputHeader('Description:');
        $this->cellIndent();
        $this->multiCell(text: $description, align: PdfTextAlignment::LEFT);
        $this->lineBreak(1.0);
    }

    private function outputHeader(string $text): void
    {
        PdfStyle::getHeaderStyle()->apply($this);
        $this->cell(text: $text, move: PdfMove::NEW_LINE);
        $this->resetStyle();
    }

    private function outputHelp(string $help): void
    {
        if ('' === $help) {
            return;
        }
        $this->outputHeader('Help:');
        $this->multiCell(text: $this->replaceHtml($help), align: PdfTextAlignment::LEFT);
    }

    private function outputName(string $name): void
    {
        $this->outputHeader('Command:');
        $this->cellIndent();
        $this->cell(text: $name, move: PdfMove::NEW_LINE);
        $this->lineBreak(1.0);
    }

    /**
     * @psalm-param array<string, OptionType> $options
     */
    private function outputOptions(array $options): void
    {
        if ([] === $options) {
            return;
        }

        $this->outputHeader('Options:');
        foreach ($options as $option) {
            $this->cellIndent();
            $this->fixedCell($this->buildOption($option['name'], $option['shortcut']));
            $this->multiCell(
                text: $this->encodeDescription($option['description'], $option['display']),
                align: PdfTextAlignment::LEFT
            );
        }
        $this->lineBreak(1.0);
    }

    /**
     * @param string[] $usage
     */
    private function outputUsage(array $usage): void
    {
        if ([] === $usage) {
            return;
        }

        $this->outputHeader('Usage:');
        $this->cellIndent();
        $this->applyFixedStyle(PdfFont::DEFAULT_SIZE);
        $this->cell(text: \implode("\n", $usage), move: PdfMove::NEW_LINE);
        $this->resetStyle();
        $this->lineBreak(1.0);
    }

    private function replaceHtml(string $str): string
    {
        return StringUtils::replace([
            '<span class="text-warning">' => '',
            '<span class="text-success">' => '',
            '</span>' => '',
            '<br>' => "\n",
        ], $str);
    }
}
