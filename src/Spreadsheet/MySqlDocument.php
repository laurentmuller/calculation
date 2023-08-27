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

/**
 * Document containing MySql configuration.
 */
class MySqlDocument extends AbstractDocument
{
    /**
     * Constructor.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function __construct(AbstractController $controller, private readonly DatabaseInfoService $service)
    {
        parent::__construct($controller);
    }

    public function render(): bool
    {
        $database = $this->service->getDatabase();
        $configuration = $this->service->getConfiguration();
        if ([] === $database && [] === $configuration) {
            return false;
        }

        $this->start($this->trans('about.mysql_version', ['%version%' => $this->service->getVersion()]));
        $sheet = $this->getActiveSheet();
        if ($this->outputArray($sheet, 'Database', $database)) {
            $sheet = $this->createSheet();
        }
        $this->outputArray($sheet, 'Configuration', $configuration);
        $this->setActiveSheetIndex(0);

        return true;
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
            $sheet->setRowValues($row++, [$key, $value]);
        }
        $sheet->setAutoSize(1, 2)
            ->finish();

        return true;
    }
}
