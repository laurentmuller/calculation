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
use App\Pdf\Enums\PdfFontName;
use App\Pdf\Enums\PdfMove;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Service\SchemaService;
use App\Utils\FormatUtils;

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

        $this->AddPage();
        $this->booleanStyle = PdfStyle::getCellStyle()
            ->setFontName(PdfFontName::ZAPFDINGBATS);

        /** @psalm-var string[] $names */
        $names = \array_column($tables, 'name');
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
            $this->tableLinks[$name] = $this->AddLink();
        }
    }

    private function createTableBuilder(string $id, PdfColumn ...$columns): PdfTableBuilder
    {
        return PdfTableBuilder::instance($this)
            ->addColumns(...$columns)
            ->startHeaderRow()
            ->add($this->trans($id), \count($columns))
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
        if (($length = $column['length']) > 0) {
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
        $builder = $this->createTableBuilder(
            'schema.fields.associations',
            PdfColumn::left($this->trans('schema.fields.name'), 100),
            PdfColumn::left($this->trans('schema.fields.table'), 100),
            PdfColumn::left($this->trans('schema.fields.relation'), 55, true)
        );
        foreach ($associations as $association) {
            $table = $association['table'];
            $link = $this->findLink($table);
            $builder->startRow()
                ->add($association['name'])
                ->addCell(new PdfCell(text: $table, link: $link))
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
        $builder = $this->createTableBuilder(
            'schema.fields.columns',
            PdfColumn::left($this->trans('schema.fields.name'), 100),
            PdfColumn::left($this->trans('schema.fields.type'), 35, true),
            PdfColumn::center($this->trans('schema.fields.required'), 20, true),
            PdfColumn::left($this->trans('schema.fields.default'), 35, true)
        );
        foreach ($columns as $column) {
            $link = $this->findLink($column['foreign_table']);
            $builder->startRow()
                ->addCell(new PdfCell(text: $column['name'], link: $link))
                ->add($this->formatType($column))
                ->add(text: $this->formatBool($column['required']), style: $this->booleanStyle)
                ->add($column['default'])
                ->completeRow();
        }
        $this->Ln();
    }

    /**
     * @psalm-param SchemaIndexType[] $indexes
     */
    private function outputIndexes(array $indexes): void
    {
        if ([] === $indexes) {
            return;
        }
        $builder = $this->createTableBuilder(
            'schema.fields.indexes',
            PdfColumn::left($this->trans('schema.fields.name'), 100),
            PdfColumn::left($this->trans('schema.fields.columns'), 100),
            PdfColumn::center($this->trans('schema.fields.primary'), 25, true),
            PdfColumn::center($this->trans('schema.fields.unique'), 30, true),
        );
        foreach ($indexes as $index) {
            $builder->startRow()
                ->add($index['name'])
                ->add(\implode(', ', $index['columns']))
                ->add(text: $this->formatBool($index['primary']), style: $this->booleanStyle)
                ->add(text: $this->formatBool($index['unique']), style: $this->booleanStyle)
                ->completeRow();
        }
        $this->Ln();
    }

    /**
     * @psalm-param SchemaTableType $table
     */
    private function outputTable(array $table): void
    {
        $this->AddPage();
        $name = $table['name'];
        if (\is_int($link = $this->findLink($name))) {
            $this->SetLink($link);
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
        $builder = PdfTableBuilder::instance($this)
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
            $builder->startRow()
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
        PdfStyle::getDefaultStyle()->setFontBold()->apply($this);
        $this->addBookmark(text: $text, y: 0);
        $this->useCellMargin(fn () => $this->Cell(txt: $text, ln: PdfMove::NEW_LINE));
        $this->Ln($this->getCellMargin());
        $this->resetStyle();
    }
}
