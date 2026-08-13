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
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use App\Pdf\Traits\PdfBooleanCellTrait;
use App\Service\FontAwesomeCellService;
use App\Service\PhpInfoService;
use App\Traits\ClosureSortTrait;

/**
 * Report for php.ini.
 *
 * @phpstan-import-type EntryType from PhpInfoService
 * @phpstan-import-type EntriesType from PhpInfoService
 */
class PhpIniReport extends AbstractReport
{
    use ClosureSortTrait;
    use PdfBooleanCellTrait;

    private ?PdfStyle $noValueStyle = null;

    public function __construct(
        DocumentHelperInterface $helper,
        private readonly PhpInfoService $infoService,
        private readonly FontAwesomeCellService $cellService
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

        $content = $this->infoService->asArray();
        if ([] === $content) {
            $this->cell(text: $this->trans('about.load.error'));

            return true;
        }

        $table = $this->createTable();
        $this->outputSingleEntry($table, 'Version', $this->infoService->getVersion());
        foreach ($content as $key => $entries) {
            $this->outputEntries($table, $key, $entries);
        }
        $this->addPageIndex();

        return true;
    }

    private function convert(float|int|string $value): string
    {
        return \htmlspecialchars_decode((string) $value);
    }

    private function createTable(): PdfGroupTable
    {
        return PdfGroupTable::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Directive', 40),
                PdfColumn::left('Local Value', 30),
                PdfColumn::left('Master Value', 30)
            )->outputHeaders();
    }

    private function getNoValueStyle(): PdfStyle
    {
        return $this->noValueStyle ??= PdfStyle::getCellStyle()
            ->setTextColor(PdfTextColor::darkGray())
            ->setFontItalic(true);
    }

    /**
     * @param positive-int $cols
     */
    private function getValueCell(string $value, int $cols = 1): PdfCell
    {
        if ($this->infoService->isColorValue($value)) {
            /** @var PdfTextColor $color */
            $color = PdfTextColor::create($value);
            $style = PdfStyle::getCellStyle()
                ->setTextColor($color);

            return PdfCell::instance(text: $value, cols: $cols, style: $style);
        }

        if ($this->infoService->isNoValue($value)) {
            return PdfCell::instance(text: $value, cols: $cols, style: $this->getNoValueStyle());
        }

        if ($this->infoService->isRedactedValue($value)) {
            return PdfCell::instance(text: $value, cols: $cols, style: $this->getBooleanStyle(false));
        }

        if ($this->infoService->isEnabledValue($value)) {
            return $this->getBooleanCell(true, $value, $cols);
        }

        if ($this->infoService->isDisabledValue($value)) {
            return $this->getBooleanCell(false, $value, $cols);
        }

        return PdfCell::instance(text: $value, cols: $cols);
    }

    /**
     * @param array{local: float|int|string, master: float|int|string, ...} $entryValue
     */
    private function outputArrayEntry(PdfGroupTable $table, string $keyValue, array $entryValue): void
    {
        $local = $this->convert($entryValue['local']);
        $master = $this->convert($entryValue['master']);
        $table->addRow(
            $keyValue,
            $this->getValueCell($local),
            $this->getValueCell($master)
        );
    }

    /**
     * @phpstan-param array<string, EntryType> $entries
     */
    private function outputEntries(PdfGroupTable $table, string $key, array $entries): void
    {
        $this->addBookmark($key);
        $table->setGroupKey($key);
        $this->sortEntries($entries);

        /** @phpstan-var EntryType $entryValue */
        foreach ($entries as $entryKey => $entryValue) {
            $keyValue = $this->convert($entryKey);
            if (\is_array($entryValue)) {
                $this->outputArrayEntry($table, $keyValue, $entryValue);
            } else {
                $this->outputSingleEntry($table, $keyValue, $entryValue);
            }
        }
        $this->resetStyle();
    }

    private function outputSingleEntry(PdfGroupTable $table, string $keyValue, float|int|string $entryValue): void
    {
        $value = $this->convert($entryValue);
        $table->addRow(
            $keyValue,
            $this->getValueCell($value, 2)
        );
    }

    /**
     * @phpstan-param array<string, EntryType> $entries
     */
    private function sortEntries(array &$entries): void
    {
        $this->sortKeysByClosures(
            $entries,
            static fn (string $a, string $b): int => \is_array($entries[$a]) <=> \is_array($entries[$b]),
            static fn (string $a, string $b): int => \strcasecmp($a, $b)
        );
    }
}
