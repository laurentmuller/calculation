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
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use App\Service\PhpInfoService;

/**
 * Report for php.ini.
 *
 * @phpstan-import-type EntryType from PhpInfoService
 * @phpstan-import-type EntriesType from PhpInfoService
 */
class PhpIniReport extends AbstractReport
{
    public function __construct(AbstractController $controller, private readonly PhpInfoService $service)
    {
        parent::__construct($controller);
        $this->setTranslatedTitle('about.php.version', ['%version%' => $this->service->getVersion()]);
        $file = \php_ini_loaded_file();
        if (\is_string($file)) {
            $this->setTranslatedDescription('log.list.file', ['%file%' => $file]);
        }
    }

    #[\Override]
    public function render(): bool
    {
        $this->addPage();

        $content = $this->service->asArray();
        if ([] === $content) {
            $this->cell(text: $this->trans('about.load.error'));

            return true;
        }

        $table = $this->createTable();
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

    private function getCellStyle(string $value): ?PdfStyle
    {
        $color = null;
        $noValue = $this->service->isNoValue($value);
        if ($this->service->isColor($value)) {
            $color = PdfTextColor::create($value);
        } elseif ($noValue || $this->service->isDisabled($value)) {
            $color = PdfTextColor::darkGray();
        }
        if (!$color instanceof PdfTextColor) {
            return null;
        }

        $style = PdfStyle::getCellStyle()
            ->setTextColor($color);
        if ($noValue) {
            $style->setFontItalic(true);
        }

        return $style;
    }

    /**
     * @param array{local: float|int|string, master: float|int|string, ...} $entryValue
     */
    private function outputArrayEntry(PdfGroupTable $table, string $keyValue, array $entryValue): void
    {
        $local = $this->convert($entryValue['local']);
        $master = $this->convert($entryValue['master']);
        $table->startRow()
            ->add($keyValue)
            ->add($local, style: $this->getCellStyle($local))
            ->add($master, style: $this->getCellStyle($master))
            ->endRow();
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
        $table->startRow()
            ->add($keyValue)
            ->add($value, 2, $this->getCellStyle($value))
            ->endRow();
    }

    /**
     * @phpstan-param array<string, EntryType> $entries
     */
    private function sortEntries(array &$entries): void
    {
        \uksort(
            $entries,
            // @phpstan-ignore ternary.shortNotAllowed
            static fn (string $a, string $b): int => \is_array($entries[$a]) <=> \is_array($entries[$b])
                ?: \strcasecmp($a, $b)
        );
    }
}
