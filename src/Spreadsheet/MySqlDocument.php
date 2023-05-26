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
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Document containing MySql configuration.
 */
class MySqlDocument extends AbstractDocument
{
    /**
     * Constructor.
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
        $row = $this->setHeaderValues([
            'Name' => Alignment::HORIZONTAL_LEFT,
            'Value' => Alignment::HORIZONTAL_LEFT,
        ]);
        if ([] !== $database) {
            $row = $this->outputArray($row, $database);
        }
        if ([] !== $configuration) {
            $this->outputArray($row, $configuration);
        }
        $this->getActiveSheet()
            ->getStyle('A:B')
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP);
        $this->setAutoSize(1)
            ->setColumnWidth(2, 50, true)
            ->finish();

        return true;
    }

    /**
     * @param array<string, string> $values
     */
    private function outputArray(int $row, array $values): int
    {
        foreach ($values as $key => $value) {
            $this->setRowValues($row++, [$key, $value]);
        }

        return $row;
    }
}
