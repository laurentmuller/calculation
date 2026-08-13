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

use App\Interfaces\DocumentHelperInterface;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfBooleanCellTrait;
use App\Service\DatabaseInfoService;
use App\Service\FontAwesomeCellService;

/**
 * Report containing database configuration.
 */
class DatabaseReport extends AbstractReport
{
    use PdfBooleanCellTrait;

    public function __construct(
        DocumentHelperInterface $helper,
        private readonly DatabaseInfoService $databaseService,
        protected readonly FontAwesomeCellService $cellService
    ) {
        parent::__construct($helper);
        $this->setTranslatedTitle('about.database.title');
    }

    #[\Override]
    public function render(): bool
    {
        $database = $this->databaseService->getDatabase();
        $configuration = $this->databaseService->getConfiguration();
        if ([] === $database && [] === $configuration) {
            return false;
        }

        $this->addPage();
        $table = PdfTable::instance($this)
            ->addColumns(
                PdfColumn::left('Name', 40),
                PdfColumn::left('Value', 60)
            )->outputHeaders();

        $this->outputArray($table, 'Database', $database);
        $this->outputArray($table, 'Configuration', $configuration);

        return true;
    }

    private function getValueCell(string $value): PdfCell
    {
        if ($this->databaseService->isEnabledValue($value)) {
            return $this->getBooleanCell(true, $value);
        }
        if ($this->databaseService->isDisabledValue($value)) {
            return $this->getBooleanCell(false, $value);
        }

        return PdfCell::instance($value);
    }

    /**
     * @param array<string, string> $values
     */
    private function outputArray(PdfTable $table, string $title, array $values): void
    {
        if ([] === $values) {
            return;
        }

        $table->singleLine($title, PdfStyle::getHeaderStyle());
        foreach ($values as $key => $value) {
            $table->addRow($key, $this->getValueCell($value));
        }
    }
}
