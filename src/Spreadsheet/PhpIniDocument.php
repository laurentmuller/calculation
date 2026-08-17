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
use App\Traits\ClosureSortTrait;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Document containing PHP configuration.
 *
 * @phpstan-import-type ValueEntryType from PhpInfoService
 * @phpstan-import-type ConfigType from PhpInfoService
 * @phpstan-import-type GroupType from PhpInfoService
 * @phpstan-import-type ModuleType from PhpInfoService
 * @phpstan-import-type PhpInfoType from PhpInfoService
 */
class PhpIniDocument extends AbstractDocument
{
    use ClosureSortTrait;

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

        $info = $this->service->getPhpInfo();
        $row = $this->outputHeaders($sheet);
        $this->outputVersion($sheet, $row++, $info['version']);
        foreach ($info['modules'] as $module) {
            $row = $this->outputModule($sheet, $row, $module);
        }
        $this->updateColumns($sheet);
        $this->updatePageSetup($sheet);
        $sheet->finish();

        return true;
    }

    /**
     * @phpstan-param ValueEntryType $entry
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

    private function outputBoldEntry(WorksheetDocument $sheet, int $row, string $value): void
    {
        $sheet->setRowValues($row, [$value]);
        $sheet->mergeContent(1, 3, $row);
        $style = $sheet->getStyle('A' . $row);
        $style->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('F5F5F5');
        $style->getFont()->setBold(true);
    }

    /**
     * @phpstan-param ConfigType $config
     */
    private function outputConfig(WorksheetDocument $sheet, int $row, array $config): int
    {
        $local = $config['local'];
        $master = $config['master'];
        $sheet->setRowValues($row, [$config['name'], $local['value'], $master['value'] ?? null]);
        $this->applyStyle($sheet, 2, $row, $local);
        if (null !== $master) {
            $this->applyStyle($sheet, 3, $row, $master);
        }

        return $row + 1;
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

        foreach ($group['configs'] as $config) {
            $row = $this->outputConfig($sheet, $row, $config);
        }

        return $row;
    }

    private function outputHeaders(WorksheetDocument $sheet): int
    {
        return $sheet->setHeaders([
            'Directive' => HeaderFormat::left(Alignment::VERTICAL_TOP),
            'Local Value' => HeaderFormat::left(Alignment::VERTICAL_TOP),
            'Master Value' => HeaderFormat::left(Alignment::VERTICAL_TOP),
        ]);
    }

    /**
     * @phpstan-param ModuleType $module
     */
    private function outputModule(WorksheetDocument $sheet, int $row, array $module): int
    {
        $this->outputBoldEntry($sheet, $row++, $module['name']);
        foreach ($module['groups'] as $group) {
            $row = $this->outputGroup($sheet, $row, $group);
        }

        return $row;
    }

    private function outputVersion(WorksheetDocument $sheet, int $row, string $version): void
    {
        $sheet->setRowValues($row, ['Version', $version]);
        $style = $sheet->getStyle('A' . $row);
        $style->getFont()->setBold(true);
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
