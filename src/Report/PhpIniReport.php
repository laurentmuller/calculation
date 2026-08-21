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

use App\Interfaces\DocumentHelperInterface;
use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\Html\HtmlColorName;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfBooleanCellTrait;
use App\Service\FontAwesomeCellService;
use App\Service\PhpInfoService;
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfBorder;

/**
 * Report containing PHP configuration.
 *
 * @phpstan-import-type EntryType from PhpInfoService
 * @phpstan-import-type ConfigType from PhpInfoService
 * @phpstan-import-type GroupType from PhpInfoService
 * @phpstan-import-type ModuleType from PhpInfoService
 */
class PhpIniReport extends AbstractReport
{
    use PdfBooleanCellTrait;

    private ?PdfStyle $noValueStyle = null;

    public function __construct(
        DocumentHelperInterface $helper,
        private readonly PhpInfoService $infoService,
        protected readonly FontAwesomeCellService $cellService
    ) {
        parent::__construct($helper);
        $this->setTranslatedTitle('about.php.title');
        $file = \php_ini_loaded_file();
        if (\is_string($file)) {
            $this->setTranslatedDescription('log.list.file', ['%file%' => $file]);
        }
    }

    #[\Override]
    public function render(): bool
    {
        $this->addPage();
        $info = $this->infoService->getPhpInfo();
        foreach ($info['modules'] as $module) {
            $this->outputModule($module);
        }
        $this->addPageIndex();

        return true;
    }

    private function createRowStyle(bool $bold = false): PdfStyle
    {
        $style = PdfStyle::default()
            ->setDrawColor(PdfDrawColor::cellBorder())
            ->setBorder(PdfBorder::bottom());
        if ($bold) {
            $style->setFontBold();
        }

        return $style;
    }

    /**
     * @param string[]|null $headers
     */
    private function createTable(?array $headers): PdfTable
    {
        $headings = null !== $headers;
        $headers ??= ['', '', ''];
        $table = PdfTable::instance($this)
            ->setHeaderStyle($this->createRowStyle(true));
        if (2 === \count($headers)) {
            $table->addColumn(PdfColumn::left($headers[0], 40));
            $table->addColumn(PdfColumn::left($headers[1], 60));
        } else {
            $table->addColumn(PdfColumn::left($headers[0], 40));
            $table->addColumn(PdfColumn::left($headers[1], 30));
            $table->addColumn(PdfColumn::left($headers[2], 30));
        }
        if ($headings) {
            $table->outputHeaders();
        } else {
            $table->setRepeatHeader(false);
        }

        return $table;
    }

    /**
     * @phpstan-param EntryType $entry
     * @phpstan-param positive-int $cols
     */
    private function getConfigCell(array $entry, int $cols = 1): PdfCell
    {
        $value = $entry['value'];
        if ($entry['color']) {
            /** @var PdfTextColor $color */
            $color = PdfTextColor::create($value);
            $style = $this->createRowStyle()
                ->setTextColor($color);

            return PdfCell::instance(text: $value, cols: $cols, style: $style);
        }

        if ($entry['no_value']) {
            return PdfCell::instance(text: $value, cols: $cols, style: $this->getNoValueStyle());
        }

        if ($entry['redacted']) {
            return PdfCell::instance(
                text: $value,
                cols: $cols,
                style: $this->getBooleanStyle(false, PdfBorder::bottom())
            );
        }

        if ($entry['enabled']) {
            return $this->getBooleanCell(
                true,
                $value,
                $cols,
                PdfBorder::bottom()
            );
        }

        if ($entry['disabled']) {
            return $this->getBooleanCell(
                false,
                $value,
                $cols,
                PdfBorder::bottom()
            );
        }

        return PdfCell::instance(text: $value, cols: $cols, style: $this->createRowStyle());
    }

    /**
     * @phpstan-param GroupType $group
     */
    private function getMinGroupHeight(array $group): float
    {
        $count = 1;
        if (null !== $group['name']) {
            ++$count;
        }
        if (null !== $group['headers']) {
            ++$count;
        }
        if (null !== $group['note']) {
            $count += $this->getLinesCount($group['note']);
        }

        return (float) $count * self::LINE_HEIGHT;
    }

    /**
     * @phpstan-param ModuleType $module
     */
    private function getMinModuleHeight(array $module): float
    {
        $group = \array_first($module['groups']);

        return null === $group ? self::LINE_HEIGHT : self::LINE_HEIGHT + $this->getMinGroupHeight($group);
    }

    private function getNoValueStyle(): PdfStyle
    {
        return $this->noValueStyle ??= $this->createRowStyle()
            ->setTextColor(PdfTextColor::darkGray())
            ->setFontItalic(true);
    }

    /**
     * @phpstan-param ConfigType $config
     */
    private function outputConfig(PdfTable $table, array $config): void
    {
        $cols = 1;
        $master = $config['master'];
        if (null === $master && $table->getColumnsCount() > 2) {
            ++$cols;
        }

        $table->startRow();
        $table->addCell(new PdfCell(text: $config['name'], style: $this->createRowStyle()));
        $table->addCell($this->getConfigCell($config['local'], $cols));
        if (null !== $master) {
            $table->addCell($this->getConfigCell($master));
        }
        $table->endRow();
    }

    /**
     * @phpstan-param GroupType $group
     */
    private function outputGroup(array $group): void
    {
        $minHeight = $this->getMinGroupHeight($group);
        if (!$this->isPrintable($minHeight)) {
            $this->addPage();
        }
        if (null !== $group['name']) {
            $this->addBookmark(text: $group['name'], level: 1);
            $this->styledCell(
                style: $this->createRowStyle(true),
                text: $group['name'],
                border: PdfBorder::bottom(),
                move: PdfMove::NEW_LINE
            );
        }
        if (null !== $group['note']) {
            $style = PdfStyle::default()
                ->setFillColor(HtmlColorName::WHITE_SMOKE);
            $this->styledMultiCell(
                style: $style,
                text: $group['note'],
                border: PdfBorder::all(),
                align: PdfTextAlignment::LEFT,
                fill: true
            );
        }

        $table = $this->createTable($group['headers']);
        foreach ($group['configs'] as $config) {
            $this->outputConfig($table, $config);
        }
        $this->lineBreak(3);
    }

    /**
     * @phpstan-param ModuleType $module
     */
    private function outputModule(array $module): void
    {
        $minHeight = $this->getMinModuleHeight($module);
        if (!$this->isPrintable($minHeight)) {
            $this->addPage();
        }
        $this->addBookmark(text: $module['name']);
        $this->styledCell(
            style: $this->createRowStyle(true)->setFontSize(11.0),
            text: $module['name'],
            border: PdfBorder::bottom(),
            move: PdfMove::NEW_LINE
        );
        foreach ($module['groups'] as $group) {
            $this->outputGroup($group);
        }
    }
}
