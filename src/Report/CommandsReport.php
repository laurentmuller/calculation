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

use App\Pdf\Html\HtmlBootstrapColor;
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
 * @extends AbstractArrayReport<CommandType[]>
 */
class CommandsReport extends AbstractArrayReport
{
    private const HELP_PATTERN = '/<span\s*class="(.*?)"\>([\s\S]*?)<\/span>/im';

    protected function doRender(array $entities): bool
    {
        $this->setTitleTrans('command.list.title');
        $this->setCellMargin(0.0);

        /** @psalm-var string $group */
        foreach ($entities as $group => $commands) {
            $first = true;
            foreach ($commands as $command) {
                $this->addPage();
                if ($first) {
                    $this->addBookmark($group, currentY: false);
                    $first = false;
                }
                $this->renderCommand($command);
            }
        }
        $this->addPageIndex();

        return true;
    }

    private function applyFixedStyle(float $size = 8.5): void
    {
        PdfStyle::default()
            ->setFontName(PdfFontName::COURIER)
            ->setFontSize($size)
            ->apply($this);
    }

    private function cellIndent(): void
    {
        $this->x += 2.0;
    }

    private function getDescriptionHelp(string $description, string $arguments): string
    {
        if (!StringUtils::isString($arguments)) {
            return $description;
        }

        return \sprintf('%s %s', $description, $arguments);
    }

    /**
     * @psalm-param CommandType $command
     *
     * @phpstan-param array $command
     */
    private function getMaxWidth(array $command): float
    {
        $width = 0.0;
        $this->applyFixedStyle();
        foreach ($command['definition']['arguments'] as $argument) {
            $width = \max($width, $this->getStringWidth($argument['name']));
        }
        foreach ($command['definition']['options'] as $option) {
            $width = \max($width, $this->getStringWidth($option['name_shortcut']));
        }
        $this->resetStyle();

        return $width + 2.0;
    }

    /**
     * @psalm-param array<string, ArgumentType> $arguments
     */
    private function renderArguments(array $arguments, float $width): void
    {
        if ([] === $arguments) {
            return;
        }

        $this->renderHeader('command.list.fields.arguments');
        foreach ($arguments as $argument) {
            $this->cellIndent();
            $this->renderFixedCell($argument['name'], $width);
            $this->renderStyledHelp($this->getDescriptionHelp($argument['description'], $argument['arguments']));
            $this->lineBreak(0.0);
        }
        $this->lineBreak(1.0);
    }

    /**
     * @psalm-param CommandType $command
     *
     * @phpstan-param array $command
     */
    private function renderCommand(array $command): void
    {
        $name = $command['name'];
        $this->addBookmark($name, level: 1, currentY: false);
        $this->renderName($name);
        $this->renderDescription($command['description']);
        $this->renderUsage($command['usage']);
        $width = $this->getMaxWidth($command);
        $this->renderArguments($command['definition']['arguments'], $width);
        $this->renderOptions($command['definition']['options'], $width);
        $this->renderHelp($command['help']);
    }

    private function renderDescription(string $description): void
    {
        if (!StringUtils::isString($description)) {
            return;
        }

        $this->renderHeader('command.list.fields.description');
        $this->cellIndent();
        $this->multiCell(text: $description, align: PdfTextAlignment::LEFT);
        $this->lineBreak(1.0);
    }

    private function renderFixedCell(string $text, float $width): void
    {
        $this->applyFixedStyle();
        HtmlBootstrapColor::SUCCESS->applyTextColor($this);
        $this->cell($width, text: $text);
        $this->resetStyle();
    }

    private function renderHeader(string $id): void
    {
        PdfStyle::getHeaderStyle()->apply($this);
        $this->cell(text: $this->trans($id), move: PdfMove::NEW_LINE);
        $this->resetStyle();
    }

    private function renderHelp(string $help): void
    {
        if (!StringUtils::isString($help)) {
            return;
        }

        $this->renderHeader('command.list.fields.help');
        $this->cellIndent();
        $this->renderStyledHelp($help);
    }

    private function renderName(string $name): void
    {
        $this->renderHeader('command.list.fields.command');
        $this->cellIndent();
        $this->cell(text: $name, move: PdfMove::NEW_LINE);
        $this->lineBreak(1.0);
    }

    /**
     * @psalm-param array<string, OptionType> $options
     */
    private function renderOptions(array $options, float $width): void
    {
        if ([] === $options) {
            return;
        }

        $this->renderHeader('command.list.fields.options');
        foreach ($options as $option) {
            $this->cellIndent();
            $this->renderFixedCell($option['name_shortcut'], $width);
            $this->renderStyledHelp($this->getDescriptionHelp($option['description'], $option['arguments']));
        }
        $this->lineBreak(1.0);
    }

    private function renderStyledHelp(string $help): void
    {
        $result = \preg_match_all(self::HELP_PATTERN, $help, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE);
        if (false === $result || 0 === $result) {
            $this->multiCell(text: $help, align: PdfTextAlignment::LEFT);

            return;
        }

        // margin
        $margin = $this->leftMargin;
        $this->leftMargin = $this->x;

        $offset = 0;
        foreach ($matches as $match) {
            // previous chunk
            $index = $match[0][1];
            if ($index > $offset) {
                $text = \substr($help, $offset, $index - $offset);
                $this->write(\strip_tags($text));
                $offset = $index;
            }

            // current chunk
            $this->applyFixedStyle(10.0);
            HtmlBootstrapColor::parseTextColor($match[1][0])?->apply($this);
            $this->write($match[2][0]);
            $this->resetStyle();

            // move
            $offset += \strlen($match[0][0]);
        }

        // last chunk
        if ($offset < \strlen($help)) {
            $this->write(\substr($help, $offset));
        }

        // restore
        $this->leftMargin = $margin;
        $this->lineBreak();
    }

    /**
     * @param string[] $usage
     */
    private function renderUsage(array $usage): void
    {
        if ([] === $usage) {
            return;
        }

        $this->renderHeader('command.list.fields.usage');
        $this->cellIndent();
        $this->applyFixedStyle(9.5);
        $this->cell(text: \implode("\n", $usage), move: PdfMove::NEW_LINE);
        $this->resetStyle();
        $this->lineBreak(1.0);
    }
}
