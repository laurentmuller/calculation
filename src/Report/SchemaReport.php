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
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Service\SchemaService;
use App\Traits\ArrayTrait;
use App\Utils\FormatUtils;
use fpdf\PdfFontName;
use fpdf\PdfMove;

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
    use ArrayTrait;

    private ?PdfStyle $booleanStyle = null;

    /**
     * @var array<string, int>
     */
    private array $tableLinks = [];

    public function __construct(AbstractController $controller, private readonly SchemaService $service)
    {
        parent::__construct($controller);
        $this->setTitleTrans('schema.name', [], true);
        $this->setDescription($this->trans('schema.description'));
    }

    public function render(): bool
    {
        $tables = $this->service->getTables();
        if ([] === $tables) {
            return false;
        }

        $this->addPage();
        $this->booleanStyle = PdfStyle::getCellStyle()
            ->setFontName(PdfFontName::ZAPFDINGBATS);

        /** @psalm-var string[] $names */
        $names = $this->getColumn($tables, 'name');
        $this->createLinks($names);

        $this->outputTables($tables);
        foreach ($names as $name) {
            $table = $this->service->getTable($name);
            $this->outputTable($table);
        }
        $this->addPageIndex();

        return true;
    }

    /**
     * @param string[] $names
     */
    private function createLinks(array $names): void
    {
        foreach ($names as $name) {
            $this->tableLinks[$name] = $this->addLink();
        }
    }

    private function createTable(string $id, PdfColumn ...$columns): PdfTable
    {
        /** @psalm-var positive-int $cols */
        $cols = \count($columns);

        return PdfTable::instance($this)
            ->addColumns(...$columns)
            ->startHeaderRow()
            ->add($this->trans($id), $cols)
            ->completeRow()
            ->outputHeaders();
    }

    private function findLink(?string $name): int|string
    {
        return $this->tableLinks[$name ?? ''] ?? '';
    }

    private function formatBool(bool $value): ?string
    {
        return $value ? '3' : null;
    }

    private function formatInverse(bool $inverse): string
    {
        $id = $inverse ? 'schema.table.one_to_many' : 'schema.table.many_to_one';

        return $this->trans($id);
    }

    /**
     * @psalm-param SchemaColumnType $column
     */
    private function formatType(array $column): string
    {
        $length = $column['length'];
        if ($length > 0) {
            return \sprintf('%s(%d)', $column['type'], $length);
        }

        return $column['type'];
    }

    /**
     * @psalm-param SchemaAssociationType[] $associations
     */
    private function outputAssociations(array $associations): void
    {
        if ([] === $associations) {
            return;
        }
        $table = $this->createTable(
            'schema.fields.associations',
            PdfColumn::left($this->trans('schema.fields.name'), 100),
            PdfColumn::left($this->trans('schema.fields.table'), 100),
            PdfColumn::left($this->trans('schema.fields.relation'), 55, true)
        );
        foreach ($associations as $association) {
            $name = $association['table'];
            $link = $this->findLink($name);
            $table->startRow()
                ->add($association['name'])
                ->addCell(new PdfCell(text: $name, link: $link))
                ->add($this->formatInverse($association['inverse']))
                ->completeRow();
        }
    }

    /**
     * @psalm-param SchemaColumnType[] $columns
     */
    private function outputColumns(array $columns): void
    {
        if ([] === $columns) {
            return;
        }
        $table = $this->createTable(
            'schema.fields.columns',
            PdfColumn::left($this->trans('schema.fields.name'), 100),
            PdfColumn::left($this->trans('schema.fields.type'), 35, true),
            PdfColumn::center($this->trans('schema.fields.required'), 20, true),
            PdfColumn::left($this->trans('schema.fields.default'), 35, true)
        );
        foreach ($columns as $column) {
            $link = $this->findLink($column['foreign_table']);
            $table->startRow()
                ->addCell(new PdfCell(text: $column['name'], link: $link))
                ->add($this->formatType($column))
                ->add(text: $this->formatBool($column['required']), style: $this->booleanStyle)
                ->add($column['default'])
                ->completeRow();
        }
        $this->lineBreak();
    }

    /**
     * @psalm-param SchemaIndexType[] $indexes
     */
    private function outputIndexes(array $indexes): void
    {
        if ([] === $indexes) {
            return;
        }
        $table = $this->createTable(
            'schema.fields.indexes',
            PdfColumn::left($this->trans('schema.fields.name'), 100),
            PdfColumn::left($this->trans('schema.fields.columns'), 100),
            PdfColumn::center($this->trans('schema.fields.primary'), 25, true),
            PdfColumn::center($this->trans('schema.fields.unique'), 30, true),
        );
        foreach ($indexes as $index) {
            $table->startRow()
                ->add($index['name'])
                ->add(\implode(', ', $index['columns']))
                ->add(text: $this->formatBool($index['primary']), style: $this->booleanStyle)
                ->add(text: $this->formatBool($index['unique']), style: $this->booleanStyle)
                ->completeRow();
        }
        $this->lineBreak();
    }

    /**
     * @psalm-param SchemaTableType $table
     */
    private function outputTable(array $table): void
    {
        $this->addPage();
        $name = $table['name'];
        $link = $this->findLink($name);
        if (\is_int($link)) {
            $this->setLink($link);
        }
        $this->outputTitle('schema.table.title', ['%name%' => $name]);
        $this->outputColumns($table['columns']);
        $this->outputIndexes($table['indexes']);
        $this->outputAssociations($table['associations']);
    }

    /**
     * @psalm-param SchemaSoftTableType[] $tables
     */
    private function outputTables(array $tables): void
    {
        $this->outputTitle('schema.index.title');
        $instance = PdfTable::instance($this)
            ->addColumns(
                PdfColumn::left($this->trans('schema.fields.name'), 100),
                PdfColumn::right($this->trans('schema.fields.columns'), 19, true),
                PdfColumn::right($this->trans('schema.fields.records'), 30, true),
                PdfColumn::right($this->trans('schema.fields.indexes'), 17, true),
                PdfColumn::right($this->trans('schema.fields.associations'), 25, true)
            )->outputHeaders();
        foreach ($tables as $table) {
            $name = $table['name'];
            $link = $this->findLink($name);
            $instance->startRow()
                ->addCell(new PdfCell(text: $name, link: $link))
                ->addValues(
                    FormatUtils::formatInt($table['columns']),
                    FormatUtils::formatInt($table['records']),
                    FormatUtils::formatInt($table['indexes']),
                    FormatUtils::formatInt($table['associations'])
                )
                ->completeRow();
        }
    }

    private function outputTitle(string $id, array $parameters = []): void
    {
        $text = $this->trans($id, $parameters);
        PdfStyle::default()->setFontBold()->apply($this);
        $this->addBookmark(text: $text, currentY: false);
        $this->useCellMargin(fn () => $this->cell(text: $text, move: PdfMove::NEW_LINE));
        $this->lineBreak($this->getCellMargin());
        $this->resetStyle();
    }
}
