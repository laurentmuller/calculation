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
use App\Model\FontAwesomeImage;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfFontAwesomeCell;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Report\Table\ReportTable;
use App\Service\FontAwesomeImageService;
use App\Service\SchemaService;
use App\Traits\ArrayTrait;
use fpdf\Enums\PdfFontName;
use fpdf\Enums\PdfMove;

/**
 * Report to display database schema.
 *
 * @psalm-import-type SchemaColumnType from SchemaService
 * @psalm-import-type SchemaIndexType from SchemaService
 * @psalm-import-type SchemaAssociationType from SchemaService
 * @psalm-import-type SchemaTableType from SchemaService
 */
class SchemaReport extends AbstractReport
{
    use ArrayTrait;

    private ?PdfStyle $booleanStyle = null;
    private ?PdfCell $cellManyToOne = null;
    private ?PdfCell $cellOneToMany = null;

    /**
     * @psalm-var array<string, int>
     */
    private array $tableLinks = [];

    public function __construct(
        AbstractController $controller,
        private readonly SchemaService $schemaService,
        private readonly FontAwesomeImageService $imageService
    ) {
        parent::__construct($controller);
        $this->setTitleTrans(id: 'schema.name', isUTF8: true);
        $this->setDescriptionTrans('schema.description');
    }

    #[\Override]
    public function render(): bool
    {
        $tables = $this->schemaService->getTables();
        if ([] === $tables) {
            return false;
        }

        $this->addPage();
        $this->booleanStyle = PdfStyle::getCellStyle()
            ->setFontName(PdfFontName::ZAPFDINGBATS);
        $this->createLinks(\array_keys($tables));
        $this->outputTables($tables);
        foreach ($tables as $table) {
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

        return ReportTable::fromReport($this)
            ->addColumns(...$columns)
            ->startHeaderRow()
            ->addCellTrans($id, cols: $cols)
            ->completeRow()
            ->outputHeaders();
    }

    private function findLink(?string $name): ?int
    {
        return $this->tableLinks[$name ?? ''] ?? null;
    }

    private function formatBool(bool $value): ?string
    {
        return $value ? '3' : null;
    }

    /**
     * @psalm-param SchemaColumnType $column
     */
    private function formatType(array $column): string
    {
        $type = $column['type'];
        $length = $column['length'];

        return $length > 0 ? \sprintf('%s(%d)', $type, $length) : $type;
    }

    private function getCellImage(string $id, string $icon): PdfCell
    {
        $text = $this->trans('schema.table.' . $id);
        $image = $this->imageService->getImage('solid/' . $icon);
        if ($image instanceof FontAwesomeImage) {
            return new PdfFontAwesomeCell($image, $text);
        }

        return new PdfCell($text);
    }

    private function getCellManyToOne(): PdfCell
    {
        if (!$this->cellManyToOne instanceof PdfCell) {
            $this->cellManyToOne = $this->getCellImage('many_to_one', 'arrow-right-from-bracket');
        }

        return $this->cellManyToOne;
    }

    private function getCellOneToMany(): PdfCell
    {
        if (!$this->cellOneToMany instanceof PdfCell) {
            $this->cellOneToMany = $this->getCellImage('one_to_many', 'arrow-right-to-bracket');
        }

        return $this->cellOneToMany;
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
            $this->leftColumn('schema.fields.name', 100),
            $this->leftColumn('schema.fields.table', 100),
            $this->leftColumn('schema.fields.relation', 55, true)
        );
        $width = $this->getPrintableWidth();
        foreach ($associations as $association) {
            $x = $this->getX();
            $y = $this->getY();
            $table->startRow()
                ->add($association['name'])
                ->add($association['table'])
                ->addCell($association['inverse'] ? $this->getCellOneToMany() : $this->getCellManyToOne())
                ->endRow();
            $link = $this->findLink($association['table']);
            if (self::isLink($link)) {
                $this->link($x, $y, $width, $this->getY() - $y, $link);
            }
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
            $this->leftColumn('schema.fields.name', 100),
            $this->leftColumn('schema.fields.type', 35, true),
            $this->centerColumn('schema.fields.required', 25, true),
            $this->leftColumn('schema.fields.default', 30, true)
        );
        $width = $this->getPrintableWidth();
        foreach ($columns as $column) {
            $x = $this->getX();
            $y = $this->getY();
            $table->startRow()
                ->add($column['name'])
                ->add($this->formatType($column))
                ->add($this->formatBool($column['required']), style: $this->booleanStyle)
                ->add($column['default'])
                ->endRow();
            $link = $this->findLink($column['foreign_table']);
            if (self::isLink($link)) {
                $this->link($x, $y, $width, $this->getY() - $y, $link);
            }
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
            $this->leftColumn('schema.fields.name', 100),
            $this->leftColumn('schema.fields.columns', 100),
            $this->centerColumn('schema.fields.primary', 25, true),
            $this->centerColumn('schema.fields.unique', 30, true),
        );
        foreach ($indexes as $index) {
            $table->startRow()
                ->add($index['name'])
                ->add(\implode(', ', $index['columns']))
                ->add($this->formatBool($index['primary']), style: $this->booleanStyle)
                ->add($this->formatBool($index['unique']), style: $this->booleanStyle)
                ->endRow();
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
     * @psalm-param SchemaTableType[] $tables
     */
    private function outputTables(array $tables): void
    {
        $this->outputTitle('schema.index.title');
        $instance = ReportTable::fromReport($this)
            ->addColumns(
                $this->leftColumn('schema.fields.name', 100),
                $this->rightColumn('schema.fields.columns', 19, true),
                $this->rightColumn('schema.fields.records', 30, true),
                $this->rightColumn('schema.fields.size', 20, true),
                $this->rightColumn('schema.fields.indexes', 17, true),
                $this->rightColumn('schema.fields.associations', 25, true)
            )->outputHeaders();
        $width = $this->getPrintableWidth();
        foreach ($tables as $table) {
            $x = $this->getX();
            $y = $this->getY();
            $instance->startRow()
                ->add($table['name'])
                ->addCellInt($table['columns'])
                ->addCellInt($table['records'])
                ->addCellAmount($table['size'])
                ->addCellInt($table['indexes'])
                ->addCellInt($table['associations'])
                ->endRow();
            $link = $this->findLink($table['name']);
            if (self::isLink($link)) {
                $this->link($x, $y, $width, $this->getY() - $y, $link);
            }
        }
    }

    private function outputTitle(string $id, array $parameters = []): void
    {
        $text = $this->trans($id, $parameters);
        PdfStyle::default()->setFontBold()->apply($this);
        $this->addBookmark(text: $text, currentY: false);
        $this->useCellMargin(fn (): static => $this->cell(text: $text, move: PdfMove::NEW_LINE));
        $this->lineBreak($this->getCellMargin());
        $this->resetStyle();
    }
}
