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
use App\Model\CalculationsState;
use App\Model\CalculationsStateItem;
use App\Model\CalculationsTotal;
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\Events\PdfCellTextEvent;
use App\Pdf\Events\PdfPdfDrawHeadersEvent;
use App\Pdf\Interfaces\PdfChartInterface;
use App\Pdf\Interfaces\PdfDrawCellTextInterface;
use App\Pdf\Interfaces\PdfDrawHeadersInterface;
use App\Pdf\PdfCell;
use App\Pdf\PdfStyle;
use App\Pdf\Traits\PdfChartLegendTrait;
use App\Pdf\Traits\PdfPieChartTrait;
use App\Report\Table\ReportTable;
use App\Table\CalculationTable;
use App\Utils\FormatUtils;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\Enums\PdfTextAlignment;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Report for calculations by states.
 *
 * @extends AbstractArrayReport<CalculationsStateItem>
 */
class CalculationByStateReport extends AbstractArrayReport implements PdfChartInterface, PdfDrawCellTextInterface, PdfDrawHeadersInterface
{
    use PdfChartLegendTrait;
    use PdfPieChartTrait;

    private ?CalculationsStateItem $currentEntity = null;
    private float $minMargin;
    private CalculationsTotal $total;

    public function __construct(
        AbstractController $controller,
        CalculationsState $state,
        private readonly UrlGeneratorInterface $generator
    ) {
        parent::__construct($controller, $state->items);
        $this->setTranslatedTitle('chart.state.title');
        $this->minMargin = $controller->getMinMargin();
        $this->total = $state->total;
    }

    #[\Override]
    public function drawCellText(PdfCellTextEvent $event): bool
    {
        if (0 !== $event->index || !$this->currentEntity instanceof CalculationsStateItem) {
            return false;
        }
        if (!$this->applyFillColor($this->currentEntity)) {
            return false;
        }

        $this->drawStateRect($event);
        $this->drawStateText($event);

        return true;
    }

    #[\Override]
    public function drawHeaders(PdfPdfDrawHeadersEvent $event): bool
    {
        $cells = [];
        $table = $event->table;
        $style = $event->headerStyle;
        $columns = $event->getColumns();
        foreach ($columns as $index => $column) {
            switch ($index) {
                case 0:
                case 3:
                    $cells[] = new PdfCell(
                        $column->getText(),
                        1,
                        $style,
                        $column->getAlignment()
                    );
                    break;
                case 1:
                case 4:
                case 6:
                    $cells[] = new PdfCell(
                        $column->getText(),
                        2,
                        $style,
                        PdfTextAlignment::CENTER
                    );
                    break;
            }
        }
        $table->addRow(...$cells);

        return true;
    }

    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->addPage();
        $this->renderChart($entities);
        $this->renderTable($entities);

        return true;
    }

    private function applyFillColor(CalculationsStateItem $entity): bool
    {
        $color = PdfFillColor::create($entity->color);
        if (!$color instanceof PdfFillColor) {
            return false;
        }
        $color->apply($this);

        return true;
    }

    private function createTable(): ReportTable
    {
        return ReportTable::fromReport($this)
            ->setHeadersListener($this)
            ->setTextListener($this)
            ->addColumns(
                $this->leftColumn('calculation.fields.state', 20),
                $this->rightColumn('calculation.list.title', 18, true),
                $this->rightColumn('', 18, true),
                $this->rightColumn('calculationgroup.fields.amount', 24, true),
                $this->rightColumn('calculation.fields.margin', 24, true),
                $this->rightColumn('', 14, true),
                $this->rightColumn('calculation.fields.total', 24, true),
                $this->rightColumn('', 18, true)
            )->outputHeaders();
    }

    private function drawStateRect(PdfCellTextEvent $event): void
    {
        $bounds = $event->bounds;
        $parent = $event->getDocument();
        $margin = $parent->getCellMargin();
        $parent->rect(
            $bounds->x + $margin,
            $bounds->y + $margin,
            self::LINE_HEIGHT,
            $bounds->height - 2.0 * $margin,
            PdfRectangleStyle::BOTH
        );
    }

    private function drawStateText(PdfCellTextEvent $event): void
    {
        $offset = 6.0;
        $parent = $event->getDocument();
        $parent->setX($event->bounds->x + $offset);
        $parent->cell(
            width: $event->bounds->width - $offset,
            height: $event->height,
            text: $event->text,
            align: $event->align
        );
    }

    private function getPercentCell(
        float $value,
        int $decimals = 2,
        bool $useStyle = false,
        bool $bold = false
    ): PdfCell {
        $text = FormatUtils::formatPercent($value, true, $decimals);
        $style = $bold ? PdfStyle::getHeaderStyle() : PdfStyle::getCellStyle();
        if ($useStyle && $this->isMinMargin($value)) {
            $style->setTextColor(PdfTextColor::red());
        }

        return new PdfCell($text, style: $style);
    }

    private function getURL(int $id): string
    {
        return $this->generator->generate('calculation_index', [CalculationTable::PARAM_STATE => $id]);
    }

    private function isMinMargin(float $value): bool
    {
        return !$this->isFloatZero($value) && $value < $this->minMargin;
    }

    /**
     * @phpstan-param CalculationsStateItem[] $entities
     */
    private function renderChart(array $entities): void
    {
        $margin = $this->getLeftMargin();
        $printableWidth = $this->getPrintableWidth();
        $top = $this->getTopMargin() + $this->getHeader()->getHeight() + self::LINE_HEIGHT;
        $radius = $printableWidth / 4.0;
        $centerX = $margin + $printableWidth / 2.0;
        $centerY = $top + $radius;
        $rows = \array_map(fn (CalculationsStateItem $entity): array => [
            'label' => $entity->code,
            'color' => $entity->color,
            'value' => $entity->total,
        ], $entities);

        $this->renderPieChart($centerX, $centerY, $radius, $rows);
        $this->setY($centerY + $radius + self::LINE_HEIGHT);
        $this->legends($rows, true);
        $this->lineBreak();
    }

    /**
     * @phpstan-param CalculationsStateItem[] $entities
     */
    private function renderTable(array $entities): void
    {
        $table = $this->createTable();
        $width = $this->getPrintableWidth();
        foreach ($entities as $entity) {
            $x = $this->getX();
            $y = $this->getY();
            $this->currentEntity = $entity;
            $table->startRow()
                ->add($entity->code)
                ->addCellInt($entity->count)
                ->addCell($this->getPercentCell($entity->calculationsPercent))
                ->addCellAmount($entity->items)
                ->addCellAmount($entity->marginAmount)
                ->addCell($this->getPercentCell($entity->marginPercent, 0, true))
                ->addCellAmount($entity->total)
                ->addCell($this->getPercentCell($entity->totalPercent))
                ->endRow();
            $link = $this->getURL($entity->id);
            $this->link($x, $y, $width, $this->getY() - $y, $link);
        }
        $this->currentEntity = null;

        // total
        $total = $this->total;
        $table->startHeaderRow()
            ->addCellTrans('calculation.fields.total')
            ->addCellInt($total->count)
            ->addCell($this->getPercentCell(1.0, bold: true))
            ->addCellAmount($total->items)
            ->addCellAmount($total->marginAmount)
            ->addCell($this->getPercentCell($total->marginPercent, 0, bold: true))
            ->addCellAmount($total->total)
            ->addCell($this->getPercentCell(1.0, bold: true))
            ->endRow();
    }
}
