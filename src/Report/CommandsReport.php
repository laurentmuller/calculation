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

use App\Pdf\Colors\PdfTextColor;
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
    private const string CLASS_PATTERN = '/<span\s*class="(.*?)">([\s\S]*?)<\/span>/im';
    private const string LINK_PATTERN = '/<a href="(.*)">(.*)<\/a>/m';

    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->setCellMargin(0.0);
        $this->setTranslatedTitle('command.list.title');

        /** @var string $group */
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

    private function getDescriptionHelp(string $description, string $arguments): string
    {
        if (!StringUtils::isString($arguments)) {
            return $description;
        }

        return \sprintf('%s %s', $description, $arguments);
    }

    private function getFixedStyle(float $size = 8.5): PdfStyle
    {
        return PdfStyle::default()
            ->setFontName(PdfFontName::COURIER)
            ->setFontSize($size);
    }

    /**
     * @phpstan-param CommandType $command
     */
    private function getMaxWidth(array $command): float
    {
        $width = 0.0;
        $this->useStyle(
            style: $this->getFixedStyle(),
            callable: function () use (&$width, $command): void {
                foreach ($command['arguments'] as $argument) {
                    $width = \max($width, $this->getStringWidth($argument['name']));
                }
                foreach ($command['options'] as $option) {
                    $width = \max($width, $this->getStringWidth($option['shortcutName']));
                }
            }
        );

        return \ceil($width) + 1.0;
    }

    private function indent(): self
    {
        $this->x += 2.0;

        return $this;
    }

    private function outputHelp(string $text): self
    {
        $text = \strip_tags($text, '<a>');
        if (!StringUtils::pregMatchAll(self::LINK_PATTERN, $text, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE)) {
            $this->write($text);

            return $this;
        }

        $offset = 0;
        foreach ($matches as $match) {
            // previous chunk
            $index = $match[0][1];
            if ($index > $offset) {
                $this->write(\substr($text, $offset, $index - $offset));
                $offset = $index;
            }
            // current chunk (link)
            $style = PdfStyle::default()
                ->setTextColor(HtmlBootstrapColor::PRIMARY);
            $this->useStyle(
                style: $style,
                callable: fn (): static => $this->write($match[2][0], link: $match[1][0])
            );
            // move
            $offset += \strlen($match[0][0]);
        }
        // last chunk
        if ($offset < \strlen($text)) {
            $this->write(\substr($text, $offset));
        }

        return $this;
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
            $this->indent()
                ->renderFixedCell($argument['name'], $width)
                ->renderStyledHelp($help)
                ->lineBreak(0.0);
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

        $this->renderHeader('command.list.fields.description')
            ->indent()
            ->multiCell(text: $description, align: PdfTextAlignment::LEFT)
            ->lineBreak(1.0);
    }

    private function renderFixedCell(string $text, float $width): self
    {
        $style = $this->getFixedStyle()
            ->setTextColor(HtmlBootstrapColor::SUCCESS);

        return $this->styledCell(
            style: $style,
            width: $width,
            text: $text
        );
    }

    private function renderHeader(string $id): self
    {
        return $this->styledCell(
            style: PdfStyle::getHeaderStyle(),
            text: $this->trans($id),
            move: PdfMove::NEW_LINE
        );
    }

    private function renderHelp(string $help): void
    {
        if (!StringUtils::isString($help)) {
            return;
        }

        $this->renderHeader('command.list.fields.help')
            ->indent()
            ->renderStyledHelp($help);
    }

    private function renderName(string $name): void
    {
        $this->renderHeader('command.list.fields.command')
            ->indent()
            ->cell(text: $name, move: PdfMove::NEW_LINE)
            ->lineBreak(1.0);
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
            $this->indent()
                ->renderFixedCell($option['shortcutName'], $width)
                ->renderStyledHelp($help);
        }
        $this->lineBreak(1.0);
    }

    private function renderStyledHelp(string $help): self
    {
        // margin
        $oldMargin = $this->getLeftMargin();
        $this->setLeftMargin($this->x);

        // find classes
        $help = \str_replace(' target="_blank" rel="noopener noreferrer"', '', $help);
        if (!StringUtils::pregMatchAll(self::CLASS_PATTERN, $help, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE)) {
            return $this->outputHelp($help)
                ->setLeftMargin($oldMargin)
                ->lineBreak();
        }

        $offset = 0;
        foreach ($matches as $match) {
            // previous chunk
            $index = $match[0][1];
            if ($index > $offset) {
                $text = \substr($help, $offset, $index - $offset);
                $this->outputHelp($text);
                $offset = $index;
            }
            // current chunk
            $color = HtmlBootstrapColor::parseTextColor($match[1][0])
                ?? PdfTextColor::default();
            $style = $this->getFixedStyle(10.0)
                ->setTextColor($color);
            $this->useStyle(
                style: $style,
                callable: fn (): self => $this->outputHelp($match[2][0])
            );
            // move
            $offset += \strlen($match[0][0]);
        }
        // last chunk
        if ($offset < \strlen($help)) {
            $this->outputHelp(\substr($help, $offset));
        }

        // restore
        return $this->setLeftMargin($oldMargin)
            ->lineBreak();
    }

    /**
     * @param string[] $usage
     */
    private function renderUsage(array $usage): void
    {
        if ([] === $usage) {
            return;
        }

        $this->renderHeader('command.list.fields.usage')
            ->indent()
            ->styledCell(
                style: $this->getFixedStyle(9.5),
                text: \implode(StringUtils::NEW_LINE, $usage),
                move: PdfMove::NEW_LINE
            )
            ->lineBreak(1.0);
    }
}
