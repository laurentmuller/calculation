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

namespace App\Report;

use App\Controller\AbstractController;
use App\Pdf\PdfColumn;
use App\Pdf\PdfException;
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use App\Service\DatabaseInfoService;

/**
 * Report for MySql.
 */
class MySqlReport extends AbstractReport
{
    /**
     * Constructor.
     */
    public function __construct(AbstractController $controller, private readonly DatabaseInfoService $service)
    {
        parent::__construct($controller);
        $this->setTitleTrans('about.mysql_version', ['%version%' => $this->service->getVersion()]);
    }

    /**
     * @throws PdfException
     */
    public function render(): bool
    {
        $database = $this->service->getDatabase();
        $configuration = $this->service->getConfiguration();
        if ([] === $database && [] === $configuration) {
            return false;
        }

        $this->AddPage();
        $table = PdfGroupTable::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Name', 40),
                PdfColumn::left('Value', 60)
            )->outputHeaders();

        $this->outputArray($table, 'Database', $database);
        $this->outputArray($table, 'Configuration', $configuration);

        return true;
    }

    /**
     * @param array<string, string> $values
     *
     * @throws PdfException
     */
    private function outputArray(PdfGroupTable $table, string $title, array $values): void
    {
        if ([] !== $values) {
            $this->addBookmark($title);
            $table->setGroupKey($title);
            foreach ($values as $key => $value) {
                $table->addRow($key, $value);
            }
        }
    }
}
