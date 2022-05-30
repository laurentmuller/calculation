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
use App\Util\DatabaseInfo;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Document containing MySql configuration.
 */
class MySqlDocument extends AbstractDocument
{
    /**
     * Constructor.
     */
    public function __construct(AbstractController $controller, private readonly DatabaseInfo $info)
    {
        parent::__construct($controller);
    }

    /**
     * {@inheritDoc}
     */
    public function render(): bool
    {
        // get values
        $database = $this->info->getDatabase();
        $configuration = $this->info->getConfiguration();
        if (empty($database) && empty($configuration)) {
            return false;
        }

        // initialize
        $title = $this->trans('about.mysql');
        $version = $this->info->getVersion();
        if (!empty($version)) {
            $title .= ' ' . $version;
        }
        $this->start($title);

        if (!empty($database)) {
            $this->outputArray($database, 'Database', false);
        }

        if (!empty($configuration)) {
            $this->outputArray($configuration, 'Configuration', !empty($database));
        }

        $this->setActiveSheetIndex(0);

        return true;
    }

    /**
     * @param array<string, string> $values
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception if an exception occurs
     */
    private function outputArray(array $values, string $title, bool $create): void
    {
        if ($create) {
            $this->createSheetAndTitle($title);
        } else {
            $this->setActiveTitle($title);
        }

        $row = 1;
        $this->setHeaderValues([
            'Name' => Alignment::HORIZONTAL_LEFT,
            'Value' => Alignment::HORIZONTAL_LEFT,
        ], 1, $row++);

        foreach ($values as $key => $value) {
            $this->setRowValues($row++, [$key, $value]);
        }

        $this->setAutoSize(1)
            ->setColumnWidth(2, 50, true)
            ->setSelectedCell('A2');
    }
}
