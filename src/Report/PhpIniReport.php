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
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfBooleanCellTrait;
use App\Service\FontAwesomeCellService;
use App\Service\PhpInfoService;
use App\Traits\ClosureSortTrait;
use fpdf\Enums\PdfMove;
use fpdf\PdfBorder;

/**
 * Report containing PHP configuration.
 *
 * @phpstan-import-type ValueEntryType from PhpInfoService
 * @phpstan-import-type ConfigType from PhpInfoService
 * @phpstan-import-type GroupType from PhpInfoService
 * @phpstan-import-type ModuleType from PhpInfoService
 * @phpstan-import-type PhpInfoType from PhpInfoService
 */
class PhpIniReport extends AbstractReport
{
    use ClosureSortTrait;
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

    private function createRowStyle(bool $bold): PdfStyle
    {
        $style = PdfStyle::default()
            ->setDrawColor(PdfDrawColor::cellBorder())
            ->setBorder(PdfBorder::bottom());
        if ($bold) {
            $style->setFontBold();
        }

        return $style;
    }

    private function createTable(): PdfTable
    {
        return PdfTable::instance($this)
            ->setHeaderStyle($this->createRowStyle(true))
            ->addColumns(
                PdfColumn::left('Directive', 40),
                PdfColumn::left('Local Value', 30),
                PdfColumn::left('Master Value', 30)
            );
    }

    /**
     * @phpstan-param ValueEntryType $entry
     * @phpstan-param positive-int $cols
     */
    private function getConfigCell(array $entry, int $cols = 1): PdfCell
    {
        $value = $entry['value'];
        if ($entry['color']) {
            /** @var PdfTextColor $color */
            $color = PdfTextColor::create($value);
            $style = $this->createRowStyle(false)
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

        return PdfCell::instance(text: $value, cols: $cols, style: $this->createRowStyle(false));
    }

    /**
     * @phpstan-param GroupType $group
     */
    private function getMinGroupHeight(array $group): float
    {
        $count = 2;
        if (null !== $group['name']) {
            ++$count;
        }
        if (null !== $group['headings']) {
            ++$count;
        }
        if (null !== $group['note']) {
            $count += $this->getLinesCount($group['note']);
        }

        return (float) $count * self::LINE_HEIGHT;
    }

    private function getNoValueStyle(): PdfStyle
    {
        return $this->noValueStyle ??= $this->createRowStyle(false)
            ->setTextColor(PdfTextColor::darkGray())
            ->setFontItalic(true);
    }

    /**
     * @phpstan-param ConfigType $config
     */
    private function outputConfig(PdfTable $table, array $config): void
    {
        if (null === $config['master']) {
            $table->addRow(
                new PdfCell(text: $config['name'], style: $this->createRowStyle(false)),
                $this->getConfigCell($config['local'], 2),
            );
        } else {
            $table->addRow(
                new PdfCell(text: $config['name'], style: $this->createRowStyle(false)),
                $this->getConfigCell($config['local']),
                $this->getConfigCell($config['master']),
            );
        }
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
            $this->createRowStyle(true)
                ->updateDocument($this);
            $this->cell(
                text: $group['name'],
                move: PdfMove::NEW_LINE
            );
            $this->resetStyle();
        }
        if (null !== $group['note']) {
            $this->multiCell(text: $group['note']);
        }

        $table = $this->createTable();
        if (null !== $group['headings']) {
            $table->outputHeaders();
        }
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
        $this->addBookmark(text: $module['name']);
        PdfStyle::getBoldCellStyle()
            ->updateDocument($this);
        $this->cell(
            text: $module['name'],
            move: PdfMove::NEW_LINE
        );
        $this->resetStyle();

        foreach ($module['groups'] as $group) {
            $this->outputGroup($group);
        }
    }
}
