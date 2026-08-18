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

use App\Interfaces\DocumentHelperInterface;
use App\Service\PhpInfoService;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Document containing PHP configuration.
 *
 * @phpstan-import-type EntryType from PhpInfoService
 * @phpstan-import-type ConfigType from PhpInfoService
 * @phpstan-import-type GroupType from PhpInfoService
 * @phpstan-import-type ModuleType from PhpInfoService
 */
class PhpIniDocument extends AbstractDocument
{
    public function __construct(DocumentHelperInterface $helper, private readonly PhpInfoService $service)
    {
        parent::__construct($helper);
    }

    #[\Override]
    public function render(): bool
    {
        $this->start($this->trans('about.php.title'));
        $this->setActiveTitle('Configuration', $this->helper);

        $sheet = $this->getActiveSheet();
        $sheet->getStyle('A:C')
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_TOP);

        $row = 1;
        $info = $this->service->getPhpInfo();
        foreach ($info['modules'] as $module) {
            $row = $this->outputModule($sheet, $row, $module);
        }
        $this->updateColumns($sheet);
        $this->updatePageSetup($sheet);
        $sheet->finish();

        return true;
    }

    private function applyBoldStyle(WorksheetDocument $sheet, string $range, float $size = 11.0): void
    {
        $style = $sheet->getStyle($range);
        $style->getFill()->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('F5F5F5'));

        $style->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->setColor(new Color('7F7F7F'));

        $style->getFont()
            ->setSize($size)
            ->setBold(true);
    }

    /**
     * @phpstan-param EntryType $entry
     */
    private function applyStyle(WorksheetDocument $sheet, int $column, int $row, array $entry): void
    {
        $color = null;
        if ($entry['color']) {
            $color = \substr($entry['value'], 1);
        } elseif ($entry['no_value'] || $entry['redacted'] || $entry['disabled']) {
            $color = '7F7F7F';
        }
        if (null === $color) {
            return;
        }
        $font = $sheet->getCell([$column, $row])
            ->getStyle()->getFont();
        $font->setColor(new Color($color));
        if ($entry['no_value']) {
            $font->setItalic(true);
        }
    }

    private function outputBoldEntry(
        WorksheetDocument $sheet,
        int $row,
        string $value,
        float $size = 11.0
    ): void {
        $sheet->setRowValues($row, [$value]);
        $sheet->mergeContent(1, 3, $row);
        $this->applyBoldStyle($sheet, \sprintf('A%1$d:C%1$d', $row), $size);
    }

    /**
     * @phpstan-param ConfigType $config
     */
    private function outputConfig(WorksheetDocument $sheet, int $row, array $config): void
    {
        $local = $config['local'];
        $master = $config['master'];
        $sheet->setRowValues($row, [$config['name'], $local['value'], $master['value'] ?? null]);
        $this->applyStyle($sheet, 2, $row, $local);
        if (null === $master) {
            $sheet->mergeContent(2, 3, $row);
        } else {
            $this->applyStyle($sheet, 3, $row, $master);
        }
    }

    /**
     * @phpstan-param GroupType $group
     */
    private function outputGroup(WorksheetDocument $sheet, int $row, array $group): int
    {
        if (null !== $group['name']) {
            $this->outputBoldEntry($sheet, $row++, $group['name']);
        }
        if (null !== $group['note']) {
            $sheet->setCellValue([1, $row], $group['note']);
            $sheet->mergeContent(1, 3, $row);
            ++$row;
        }

        if (null !== $group['headers']) {
            $this->outputHeadings($sheet, $row++, $group['headers']);
        }

        foreach ($group['configs'] as $config) {
            $this->outputConfig($sheet, $row++, $config);
        }

        return $row;
    }

    /**
     * @param string[] $headers
     */
    private function outputHeadings(WorksheetDocument $sheet, int $row, array $headers): void
    {
        $sheet->setRowValues($row, [$headers[0], $headers[1], $headers[2] ?? null]);
        if (2 === \count($headers)) {
            $sheet->mergeContent(2, 3, $row);
        }
        $this->applyBoldStyle($sheet, \sprintf('A%1$d:C%1$d', $row));
    }

    /**
     * @phpstan-param ModuleType $module
     */
    private function outputModule(WorksheetDocument $sheet, int $row, array $module): int
    {
        $this->outputBoldEntry($sheet, $row++, $module['name'], 13.0);
        foreach ($module['groups'] as $group) {
            $row = $this->outputGroup($sheet, $row, $group);
        }

        return $row;
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
