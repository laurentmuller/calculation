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
use App\Pdf\Enums\PdfMove;
use App\Pdf\PdfBorder;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Service\SchemaService;
use App\Util\FormatUtils;

/**
 * Report to display database schema.
 *
 * @psalm-import-type SchemaSoftTableType from SchemaService
 * @psalm-import-type SchemaColumnType from SchemaService
 * @psalm-import-type SchemaIndexType from SchemaService
 * @psalm-import-type SchemaAssociationType from SchemaService
 * @psalm-import-type SchemaTableType from SchemaService
 */
class SchemaReport extends AbstractReport
{
    public function __construct(AbstractController $controller, private readonly SchemaService $service)
    {
        parent::__construct($controller);
        $this->setTitleTrans('schema.name', [], true);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function render(): bool
    {
        $tables = $this->service->getTables();
        if ([] === $tables) {
            return false;
        }

        $this->AddPage();
        $this->outputTables($tables);
        $names = $this->service->getTableNames();
        foreach ($names as $name) {
            /** @var SchemaTableType $table */
            $table = $this->service->getTable($name);
            $this->outputColumns($table['columns']);
            $this->outputIndexes($table['indexes']);
            $this->outputAssociations($table['associations']);
        }

        return true;
    }

    /**
     * @psalm-param SchemaAssociationType[] $associations
     */
    private function outputAssociations(array $associations): void
    {
        if ([] !== $associations) {
        }
    }

    /**
     * @psalm-param SchemaColumnType[] $columns
     */
    private function outputColumns(array $columns): void
    {
        if ([] !== $columns) {
        }
    }

    /**
     * @psalm-param SchemaIndexType[] $indexes
     */
    private function outputIndexes(array $indexes): void
    {
        if ([] !== $indexes) {
        }
    }

    /**
     * @psalm-param SchemaSoftTableType[] $tables
     */
    private function outputTables(array $tables): void
    {
        // title
        $margin = $this->setCellMargin(0);
        PdfStyle::getDefaultStyle()->setFontBold()->apply($this);
        $this->Cell(0, self::LINE_HEIGHT + 2.0, $this->trans('schema.index.title'), PdfBorder::none(), PdfMove::NEW_LINE);
        $this->setCellMargin($margin);
        $this->resetStyle();

        $builder = PdfTableBuilder::instance($this)
            ->addColumns(
                PdfColumn::left($this->trans('schema.fields.name'), 200),
                PdfColumn::right($this->trans('schema.fields.columns'), 70),
                PdfColumn::right($this->trans('schema.fields.records'), 70),
                PdfColumn::right($this->trans('schema.fields.indexes'), 70),
                PdfColumn::right($this->trans('schema.fields.associations'), 70)
            )->outputHeaders();

        foreach ($tables as $table) {
            $builder->addRow(
                $table['name'],
                FormatUtils::formatInt($table['columns']),
                FormatUtils::formatInt($table['records']),
                FormatUtils::formatInt($table['indexes']),
                FormatUtils::formatInt($table['associations'])
            );
        }
    }
}
