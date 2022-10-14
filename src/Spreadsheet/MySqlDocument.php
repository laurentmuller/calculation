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
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function __construct(AbstractController $controller, private readonly DatabaseInfoService $info)
    {
        parent::__construct($controller);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
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

        $row = $this->setHeaderValues([
            'Name' => Alignment::HORIZONTAL_LEFT,
            'Value' => Alignment::HORIZONTAL_LEFT,
        ]);

        if (!empty($database)) {
            $row = $this->outputArray($row, $database);
        }
        if (!empty($configuration)) {
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
