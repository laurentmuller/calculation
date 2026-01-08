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
use App\Pdf\Colors\PdfTextColor;
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
 * @phpstan-import-type SchemaColumnType from SchemaService
 * @phpstan-import-type SchemaIndexType from SchemaService
 * @phpstan-import-type SchemaAssociationType from SchemaService
 * @phpstan-import-type SchemaTableType from SchemaService
 */
class SchemaReport extends AbstractReport
{
    use ArrayTrait;

    private ?PdfCell $booleanCell = null;
    private ?PdfCell $manyToOneCell = null;
    private ?PdfCell $oneToManyCell = null;

    /**
     * @phpstan-var array<string, int>
     */
    private array $tableLinks = [];

    public function __construct(
        AbstractController $controller,
        private readonly SchemaService $schemaService,
        private readonly FontAwesomeImageService $imageService
    ) {
        parent::__construct($controller);
        $this->setTranslatedTitle(id: 'schema.name', isUTF8: true)
            ->setTranslatedDescription('schema.description');
    }

    #[\Override]
    public function render(): bool
    {
        $tables = $this->schemaService->getTables();
        if ([] === $tables) {
            return false;
        }

        $this->addPage();
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
        /** @var positive-int $cols */
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
        return $this->tableLinks[$name] ?? null;
    }

    /**
     * @phpstan-param SchemaColumnType $column
     */
    private function formatType(array $column): string
    {
        $type = $column['type'];
        $length = $column['length'];

        return $length > 0 ? \sprintf('%s(%d)', $type, $length) : $type;
    }

    private function getBooleanCell(bool $value): ?PdfCell
    {
        if (!$value) {
            return null;
        }
        if ($this->booleanCell instanceof PdfCell) {
            return $this->booleanCell;
        }
        $style = PdfStyle::getCellStyle()
            ->setFontName(PdfFontName::ZAPFDINGBATS)
            ->setTextColor(PdfTextColor::darkGreen());

        return $this->booleanCell = new PdfCell(text: '3', style: $style);
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

    private function getManyToOneCell(): PdfCell
    {
        return $this->manyToOneCell ??= $this->getCellImage('many_to_one', 'arrow-right-from-bracket');
    }

    private function getOneToManyCell(): PdfCell
    {
        return $this->oneToManyCell ??= $this->getCellImage('one_to_many', 'arrow-right-to-bracket');
    }

    /**
     * @phpstan-param SchemaAssociationType[] $associations
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
                ->addCell($association['inverse'] ? $this->getOneToManyCell() : $this->getManyToOneCell())
                ->endRow();
            $link = $this->findLink($association['table']);
            if (self::isLink($link)) {
                $this->link($x, $y, $width, $this->getY() - $y, $link);
            }
        }
    }

    /**
     * @phpstan-param SchemaColumnType[] $columns
     */
    private function outputColumns(array $columns): static
    {
        if ([] === $columns) {
            return $this;
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
            $table->addRow(
                $column['name'],
                $this->formatType($column),
                $this->getBooleanCell($column['required']),
                $column['default']
            );
            $link = $this->findLink($column['foreign_table']);
            if (self::isLink($link)) {
                $this->link($x, $y, $width, $this->getY() - $y, $link);
            }
        }

        return $this->lineBreak();
    }

    /**
     * @phpstan-param SchemaIndexType[] $indexes
     */
    private function outputIndexes(array $indexes): static
    {
        if ([] === $indexes) {
            return $this;
        }

        $table = $this->createTable(
            'schema.fields.indexes',
            $this->leftColumn('schema.fields.name', 100),
            $this->leftColumn('schema.fields.columns', 100),
            $this->centerColumn('schema.fields.primary', 25, true),
            $this->centerColumn('schema.fields.unique', 30, true),
        );
        foreach ($indexes as $index) {
            $table->addRow(
                $index['name'],
                \implode(', ', $index['columns']),
                $this->getBooleanCell($index['primary']),
                $this->getBooleanCell($index['unique'])
            );
        }

        return $this->lineBreak();
    }

    /**
     * @phpstan-param SchemaTableType $table
     */
    private function outputTable(array $table): void
    {
        $this->addPage();
        $name = $table['name'];
        $link = $this->findLink($name);
        if (\is_int($link)) {
            $this->setLink($link);
        }
        $this->outputTitle($name, 1)
            ->outputColumns($table['columns'])
            ->outputIndexes($table['indexes'])
            ->outputAssociations($table['associations']);
    }

    /**
     * @phpstan-param SchemaTableType[] $tables
     */
    private function outputTables(array $tables): void
    {
        $this->outputTitle($this->trans('schema.index.title'));
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
            $name = $table['name'];
            $instance->startRow()
                ->add($name)
                ->addCellInt($table['columns'])
                ->addCellInt($table['records'])
                ->addCellInt($table['size'])
                ->addCellInt($table['indexes'])
                ->addCellInt($table['associations'])
                ->endRow();
            $link = $this->findLink($name);
            if (self::isLink($link)) {
                $this->link($x, $y, $width, $this->getY() - $y, $link);
            }
        }
    }

    /**
     * @param int<0, max> $level
     */
    private function outputTitle(string $text, int $level = 0): static
    {
        PdfStyle::default()->setFontBold()->apply($this);

        return $this->addBookmark(text: $text, level: $level, currentY: false)
            ->useCellMargin(fn (): static => $this->cell(text: $text, move: PdfMove::NEW_LINE))
            ->lineBreak($this->getCellMargin())
            ->resetStyle();
    }
}
