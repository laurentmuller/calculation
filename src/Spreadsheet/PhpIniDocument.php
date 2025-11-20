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

namespace App\Spreadsheet;

use App\Controller\AbstractController;
use App\Service\PhpInfoService;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Document containing PHP configuration.
 *
 * @phpstan-import-type EntryType from PhpInfoService
 * @phpstan-import-type EntriesType from PhpInfoService
 */
class PhpIniDocument extends AbstractDocument
{
    public function __construct(AbstractController $controller, private readonly PhpInfoService $service)
    {
        parent::__construct($controller);
    }

    #[\Override]
    public function render(): bool
    {
        $content = $this->service->asArray();
        $version = $this->service->getVersion();
        $this->start($this->trans('about.php.version', ['%version%' => $version]));
        $this->setActiveTitle('Configuration', $this->controller);
        $sheet = $this->getActiveSheet();

        if ([] === $content) {
            $sheet->setCellContent(1, 1, $this->trans('about.load.error'))
                ->finish('A1');

            return true;
        }

        $row = $this->outputHeaders($sheet);
        foreach ($content as $key => $entries) {
            $row = $this->outputGroup($sheet, $row, $key);
            $row = $this->outputEntries($sheet, $row, $entries);
        }
        $this->updateColumns($sheet);
        $this->updatePageSetup($sheet);
        $sheet->finish();

        return true;
    }

    private function applyStyle(WorksheetDocument $sheet, int $column, int $row, string $value): self
    {
        $color = null;
        $noValue = $this->service->isNoValue($value);
        if ($this->service->isColor($value)) {
            $color = \substr($value, 1);
        } elseif ($noValue || $this->service->isDisabled($value)) {
            $color = '7F7F7F';
        }
        if (null === $color) {
            return $this;
        }

        $font = $sheet->getCell([$column, $row])
            ->getStyle()->getFont();
        $font->setColor(new Color($color));
        if ($noValue) {
            $font->setItalic(true);
        }

        return $this;
    }

    private function convert(float|int|string $var): string
    {
        return \htmlspecialchars_decode((string) $var);
    }

    /**
     * @param array{local: float|int|string, master: float|int|string, ...} $entryValue
     */
    private function outputArrayEntry(WorksheetDocument $sheet, int $row, string $keyValue, array $entryValue): void
    {
        $local = $this->convert($entryValue['local']);
        $master = $this->convert($entryValue['master']);
        $sheet->setCellValue([1, $row], $keyValue)
            ->setCellValue([2, $row], $local)
            ->setCellValue([3, $row], $master);
        $this->applyStyle($sheet, 2, $row, $local)
            ->applyStyle($sheet, 3, $row, $master);
    }

    /**
     * @phpstan-param array<string, EntryType> $entries
     */
    private function outputEntries(WorksheetDocument $sheet, int $row, array $entries): int
    {
        $this->sortEntries($entries);
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);

        /** @phpstan-var EntryType $entryValue */
        foreach ($entries as $entryKey => $entryValue) {
            $keyValue = $this->convert($entryKey);
            if (\is_array($entryValue)) {
                $this->outputArrayEntry($sheet, $row, $keyValue, $entryValue);
            } else {
                $this->outputSingleEntry($sheet, $row, $keyValue, $entryValue);
            }
            ++$row;
        }

        return $row;
    }

    private function outputGroup(WorksheetDocument $sheet, int $row, string $group): int
    {
        $sheet->setRowValues($row, [$group]);
        $sheet->mergeContent(1, 3, $row);
        $style = $sheet->getStyle('A' . $row);
        $style->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('F5F5F5');
        $style->getFont()->setBold(true);

        return $row + 1;
    }

    private function outputHeaders(WorksheetDocument $sheet): int
    {
        return $sheet->setHeaders([
            'Directive' => HeaderFormat::left(Alignment::VERTICAL_TOP),
            'Local Value' => HeaderFormat::left(Alignment::VERTICAL_TOP),
            'Master Value' => HeaderFormat::left(Alignment::VERTICAL_TOP),
        ]);
    }

    private function outputSingleEntry(
        WorksheetDocument $sheet,
        int $row,
        string $keyValue,
        float|int|string $entryValue
    ): void {
        $value = $this->convert($entryValue);
        $sheet->setCellValue([1, $row], $keyValue)
            ->setCellValue([2, $row], $value);
        $this->applyStyle($sheet, 2, $row, $value);
    }

    /**
     * @phpstan-param array<string, EntryType> $entries
     */
    private function sortEntries(array &$entries): void
    {
        \uksort($entries, static function (string $a, string $b) use ($entries): int {
            $result = \is_array($entries[$a]) <=> \is_array($entries[$b]);
            if (0 !== $result) {
                return $result;
            }

            return \strcasecmp($a, $b);
        });
    }

    private function updateColumns(WorksheetDocument $sheet): void
    {
        $sheet->setWrapText(2)
            ->setAutoSize(1)
            ->setColumnWidth(2, 50)
            ->setColumnWidth(3, 50, true);
    }

    private function updatePageSetup(WorksheetDocument $sheet): void
    {
        $sheet->getPageSetup()
            ->setFitToWidth(1)
            ->setFitToHeight(0);
    }
}
