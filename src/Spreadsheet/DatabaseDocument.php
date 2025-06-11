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
    private const DISABLED_VALUES = ['off', 'no', 'false', 'disabled'];

    public function __construct(AbstractController $controller, private readonly DatabaseInfoService $service)
    {
        parent::__construct($controller);
    }

    #[\Override]
    public function render(): bool
    {
        $database = $this->service->getDatabase();
        $configuration = $this->service->getConfiguration();
        if ([] === $database && [] === $configuration) {
            return false;
        }

        $color = new Color('A9A9A9');
        $this->start($this->trans('about.database'));
        $sheet = $this->getActiveSheet();
        if ($this->outputArray($sheet, 'Database', $database, $color)) {
            $sheet = $this->createSheet();
        }
        $this->outputArray($sheet, 'Configuration', $configuration, $color);
        $this->setActiveSheetIndex(0);

        return true;
    }

    private function applyStyle(WorksheetDocument $sheet, int $row, string $value, Color $color): void
    {
        if (!\in_array(\strtolower($value), self::DISABLED_VALUES, true)) {
            return;
        }
        $sheet->getCell([2, $row])
            ->getStyle()->getFont()
            ->setColor($color);
    }

    /**
     * @param array<string, string> $values
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function outputArray(WorksheetDocument $sheet, string $title, array $values, Color $color): bool
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
            $this->applyStyle($sheet, $row, $value, $color);
            ++$row;
        }
        $sheet->setAutoSize(1)
            ->setColumnWidth(2, 100, true)
            ->finish();

        return true;
    }
}
