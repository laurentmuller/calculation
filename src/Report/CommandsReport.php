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
use fpdf\Enums\PdfFontName;
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfTextAlignment;

/**
 * Report for application commands.
 *
 * @phpstan-import-type CommandType from CommandService
 * @phpstan-import-type InputType from CommandService
 *
 * @extends AbstractArrayReport<CommandType[]>
 */
class CommandsReport extends AbstractArrayReport
{
    private const string CLASS_PATTERN = '/<span\s*class="(.*?)"\>([\s\S]*?)<\/span>/im';
    private const string LINK_PATTERN = '/<a href="(.*)">(.*)<\/a>/m';

    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->setCellMargin(0.0);
        $this->setTranslatedTitle('command.list.title');

        /** @phpstan-var string $group */
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

    private function getDescriptionHelp(string $description, string $arguments): string
    {
        if (!StringUtils::isString($arguments)) {
            return $description;
        }

        return \sprintf('%s %s', $description, $arguments);
    }

    /**
     * @phpstan-param CommandType $command
     */
    private function getMaxWidth(array $command): float
    {
        $width = 0.0;
        $this->applyFixedStyle();
        $width = \array_reduce(
            $command['arguments'],
            fn (float $carry, array $argument): float => \max($carry, $this->getStringWidth($argument['name'])),
            $width
        );
        $width = \array_reduce(
            $command['options'],
            fn (float $carry, array $option): float => \max($carry, $this->getStringWidth($option['shortcutName'])),
            $width
        );
        $this->resetStyle();

        return \ceil($width) + 1.0;
    }

    private function indent(): void
    {
        $this->x += 2.0;
    }

    private function outputHelp(string $text): void
    {
        $text = \strip_tags($text, '<a>');
        if (!StringUtils::pregMatchAll(self::LINK_PATTERN, $text, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE)) {
            $this->write($text);

            return;
        }

        $offset = 0;
        /** @phpstan-var array<int, array{0: string, 1: int}> $match */
        foreach ($matches as $match) {
            // previous chunk
            $index = $match[0][1];
            if ($index > $offset) {
                $this->write(\substr($text, $offset, $index - $offset));
                $offset = $index;
            }
            // current chunk (link)
            HtmlBootstrapColor::PRIMARY->applyTextColor($this);
            $this->write($match[2][0], link: $match[1][0]);
            $this->resetStyle();
            // move
            $offset += \strlen($match[0][0]);
        }
        // last chunk
        if ($offset < \strlen($text)) {
            $this->write(\substr($text, $offset));
        }
    }

    /**
     * @phpstan-param array<string, InputType> $arguments
     */
    private function renderArguments(array $arguments, float $width): void
    {
        if ([] === $arguments) {
            return;
        }

        $this->renderHeader('command.list.fields.arguments');
        foreach ($arguments as $argument) {
            $help = $this->getDescriptionHelp($argument['description'], $argument['extra']);
            $this->indent();
            $this->renderFixedCell($argument['name'], $width);
            $this->renderStyledHelp($help);
            $this->lineBreak(0.0);
        }
        $this->lineBreak(1.0);
    }

    /**
     * @phpstan-param CommandType $command
     */
    private function renderCommand(array $command): void
    {
        $name = $command['name'];
        $this->addBookmark($name, level: 1, currentY: false);
        $this->renderName($name);
        $this->renderDescription($command['description']);
        $this->renderUsage($command['usage']);
        $width = $this->getMaxWidth($command);
        $this->renderArguments($command['arguments'], $width);
        $this->renderOptions($command['options'], $width);
        $this->renderHelp($command['help']);
    }

    private function renderDescription(string $description): void
    {
        if (!StringUtils::isString($description)) {
            return;
        }

        $this->renderHeader('command.list.fields.description');
        $this->indent();
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
        $this->indent();
        $this->renderStyledHelp($help);
    }

    private function renderName(string $name): void
    {
        $this->renderHeader('command.list.fields.command');
        $this->indent();
        $this->cell(text: $name, move: PdfMove::NEW_LINE);
        $this->lineBreak(1.0);
    }

    /**
     * @phpstan-param array<string, InputType> $options
     */
    private function renderOptions(array $options, float $width): void
    {
        if ([] === $options) {
            return;
        }

        $this->renderHeader('command.list.fields.options');
        foreach ($options as $option) {
            $help = $this->getDescriptionHelp($option['description'], $option['extra']);
            $this->indent();
            $this->renderFixedCell($option['shortcutName'], $width);
            $this->renderStyledHelp($help);
        }
        $this->lineBreak(1.0);
    }

    private function renderStyledHelp(string $help): void
    {
        // margin
        $oldMargin = $this->getLeftMargin();
        $this->setLeftMargin($this->x);

        // find classes
        $help = \str_replace(' target="_blank" rel="noopener noreferrer"', '', $help);
        if (!StringUtils::pregMatchAll(self::CLASS_PATTERN, $help, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE)) {
            $this->outputHelp($help);
            $this->setLeftMargin($oldMargin);
            $this->lineBreak();

            return;
        }

        $offset = 0;
        /** @phpstan-var array<int, array{0: string, 1: int}> $match */
        foreach ($matches as $match) {
            // previous chunk
            $index = $match[0][1];
            if ($index > $offset) {
                $text = \substr($help, $offset, $index - $offset);
                $this->outputHelp($text);
                $offset = $index;
            }
            // current chunk
            $this->applyFixedStyle(10.0);
            HtmlBootstrapColor::parseTextColor($match[1][0])?->apply($this);
            $this->outputHelp($match[2][0]);
            $this->resetStyle();
            // move
            $offset += \strlen($match[0][0]);
        }
        // last chunk
        if ($offset < \strlen($help)) {
            $this->outputHelp(\substr($help, $offset));
        }

        // restore
        $this->setLeftMargin($oldMargin);
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
        $this->indent();
        $this->applyFixedStyle(9.5);
        $this->cell(text: \implode(StringUtils::NEW_LINE, $usage), move: PdfMove::NEW_LINE);
        $this->resetStyle();
        $this->lineBreak(1.0);
    }
}
