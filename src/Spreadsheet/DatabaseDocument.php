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
use App\Service\DatabaseInfoService;
use PhpOffice\PhpSpreadsheet\Style\Color;

/**
 * Document containing database configuration.
 */
class DatabaseDocument extends AbstractDocument
{
    private ?Color $disabledColor = null;
    private ?Color $enabledColor = null;

    public function __construct(AbstractController $controller, private readonly DatabaseInfoService $service)
    {
        parent::__construct($controller);
    }

    #[\Override]
    public function render(): bool
    {
        $service = $this->service;
        $database = $service->getDatabase();
        $configuration = $service->getConfiguration();
        if ([] === $database && [] === $configuration) {
            return false;
        }

        $this->start($this->trans('about.database.title'));
        $sheet = $this->getActiveSheet();
        if ($this->outputArray($sheet, 'Database', $database)) {
            $sheet = $this->createSheet();
        }
        $this->outputArray($sheet, 'Configuration', $configuration);
        $this->setActiveSheetIndex(0);

        return true;
    }

    private function applyStyle(WorksheetDocument $sheet, int $row, string $value): void
    {
        $color = $this->getColor($value);
        if ($color instanceof Color) {
            $sheet->getCell([2, $row])
                ->getStyle()->getFont()
                ->setColor($color);
        }
    }

    private function getColor(string $value): ?Color
    {
        if ($this->service->isEnabledValue($value)) {
            return $this->enabledColor ??= new Color('008000');
        }
        if ($this->service->isDisabledValue($value)) {
            return $this->disabledColor ??= new Color('A9A9A9');
        }

        return null;
    }

    /**
     * @param array<string, string> $values
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function outputArray(WorksheetDocument $sheet, string $title, array $values): bool
    {
        if ([] === $values) {
            return false;
        }

        $sheet->setTitle($title);
        $row = $sheet->setHeaders([
            'Name' => HeaderFormat::left(),
            'Value' => HeaderFormat::left(),
        ]);
        foreach ($values as $key => $value) {
            $sheet->setRowValues($row, [$key, $value]);
            $this->applyStyle($sheet, $row, $value);
            ++$row;
        }
        $sheet->setAutoSize(1)
            ->setColumnWidth(2, 100, true)
            ->finish();

        return true;
    }
}
