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
use App\Utils\StringUtils;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Document containing PHP configuration.
 *
 * @phpstan-type EntriesType = array{local: string, master: string}|string
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
        $this->start($this->trans('about.php_version', ['%version%' => $version]));
        $this->setActiveTitle('Configuration', $this->controller);
        $sheet = $this->getActiveSheet();
        if ([] === $content) {
            $sheet->setCellContent(1, 1, $this->trans('about.error'))
                ->finish('A1');

            return true;
        }

        \ksort($content, \SORT_STRING | \SORT_FLAG_CASE);
        $row = $sheet->setHeaders([
            'Directive' => HeaderFormat::left(Alignment::VERTICAL_TOP),
            'Local Value' => HeaderFormat::left(Alignment::VERTICAL_TOP),
            'Master Value' => HeaderFormat::left(Alignment::VERTICAL_TOP),
        ]);

        /**  @phpstan-var array<string, EntriesType> $entries */
        foreach ($content as $key => $entries) {
            $row = $this->outputGroup($sheet, $row, $key);
            $row = $this->outputEntries($sheet, $row, $entries);
        }

        $sheet->setWrapText(2)
            ->setAutoSize(1)
            ->setColumnWidth(2, 50)
            ->setColumnWidth(3, 50, true)
            ->finish();

        $sheet->getPageSetup()
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        return true;
    }

    private function applyStyle(WorksheetDocument $sheet, int $column, int $row, string $var): self
    {
        $color = null;
        if (StringUtils::pregMatch('/#[\dA-Fa-f]{6}/i', $var)) {
            $color = \substr($var, 1);
        } elseif (\in_array(
            \strtolower($var),
            ['no', 'disabled', 'off', 'no value', PhpInfoService::REDACTED],
            true
        )) {
            $color = '7F7F7F';
        }
        $italic = StringUtils::equalIgnoreCase('no value', $var);
        if (null === $color && !$italic) {
            return $this;
        }

        $font = $sheet->getCell([$column, $row])
            ->getStyle()->getFont();
        if ($italic) {
            $font->setItalic(true);
        }
        if (null !== $color) {
            $font->setColor(new Color($color));
        }

        return $this;
    }

    /**
     * @param mixed $var the variable to convert
     */
    private function convert(mixed $var): string
    {
        return \htmlspecialchars_decode((string) $var);
    }

    /**
     * @phpstan-param array<string, EntriesType> $entries
     */
    private function outputEntries(WorksheetDocument $sheet, int $row, array $entries): int
    {
        $this->sortEntries($entries);
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);

        foreach ($entries as $key => $entry) {
            $keyValue = $this->convert($key);
            if (\is_array($entry)) {
                $local = $this->convert(\reset($entry));
                $master = $this->convert(\end($entry));
                $sheet->setCellValue([1, $row], $keyValue)
                    ->setCellValue([2, $row], $local)
                    ->setCellValue([3, $row], $master);
                $this->applyStyle($sheet, 2, $row, $local)
                    ->applyStyle($sheet, 3, $row, $master);
            } else {
                $entryValue = $this->convert($entry);
                $sheet->setCellValue([1, $row], $keyValue)
                    ->setCellValue([2, $row], $entryValue);
                $this->applyStyle($sheet, 2, $row, $entryValue);
            }
            ++$row;
        }

        return $row;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function outputGroup(WorksheetDocument $sheet, int $row, string $group): int
    {
        $sheet->setRowValues($row, [$group]);
        $sheet->mergeContent(1, 3, $row);
        $style = $sheet->getStyle("A$row");
        $style->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('F5F5F5');
        $style->getFont()->setBold(true);

        return $row + 1;
    }

    /**
     * @phpstan-param array<string, EntriesType> $entries
     */
    private function sortEntries(array &$entries): void
    {
        \uksort($entries, function (string $a, string $b) use ($entries): int {
            $isArrayA = \is_array($entries[$a]);
            $isArrayB = \is_array($entries[$b]);
            if ($isArrayA !== $isArrayB) {
                return $isArrayA <=> $isArrayB;
            }

            return \strcasecmp($a, $b);
        });
    }
}
