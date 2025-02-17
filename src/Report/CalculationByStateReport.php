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
use App\Traits\StateTotalsTrait;
use App\Utils\FormatUtils;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\Enums\PdfTextAlignment;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Report for calculations by states.
 *
 * @psalm-import-type QueryCalculationType from \App\Repository\CalculationStateRepository
 *
 * @extends AbstractArrayReport<QueryCalculationType>
 */
class CalculationByStateReport extends AbstractArrayReport implements PdfChartInterface, PdfDrawCellTextInterface, PdfDrawHeadersInterface
{
    use PdfChartLegendTrait;
    use PdfPieChartTrait;
    use StateTotalsTrait;

    /** @psalm-var QueryCalculationType|null */
    private ?array $currentEntity = null;
    private float $minMargin;

    /**
     * @psalm-param QueryCalculationType[] $entities
     */
    public function __construct(
        AbstractController $controller,
        array $entities,
        private readonly UrlGeneratorInterface $generator
    ) {
        parent::__construct($controller, $entities);
        $this->setTitleTrans('chart.state.title');
        $this->minMargin = $controller->getMinMargin();
    }

    #[\Override]
    public function drawCellText(PdfCellTextEvent $event): bool
    {
        if (0 !== $event->index || null === $this->currentEntity) {
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

    /**
     * @psalm-param QueryCalculationType $entity
     */
    private function applyFillColor(array $entity): bool
    {
        $color = PdfFillColor::create($entity['color']);
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
            5.0,
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

    /**
     * @psalm-param \NumberFormatter::ROUND_* $roundingMode
     */
    private function getPercentCell(
        float $value,
        int $decimals = 2,
        bool $useStyle = false,
        bool $bold = false,
        int $roundingMode = \NumberFormatter::ROUND_HALFDOWN
    ): PdfCell {
        $text = FormatUtils::formatPercent($value, true, $decimals, $roundingMode);
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
     * @psalm-param QueryCalculationType[] $entities
     */
    private function renderChart(array $entities): void
    {
        $margin = $this->getLeftMargin();
        $printableWidth = $this->getPrintableWidth();
        $top = $this->getTopMargin() + $this->getHeader()->getHeight() + self::LINE_HEIGHT;
        $radius = $printableWidth / 4.0;
        $centerX = $margin + $printableWidth / 2.0;
        $centerY = $top + $radius;
        $rows = \array_map(fn (array $entity): array => [
            'label' => $entity['code'],
            'color' => $entity['color'],
            'value' => $entity['percent_amount'],
        ], $entities);

        $this->renderPieChart($centerX, $centerY, $radius, $rows);
        $this->setY($centerY + $radius + self::LINE_HEIGHT);
        $this->legends($rows, true);
        $this->lineBreak();
    }

    /**
     * @psalm-param QueryCalculationType[] $entities
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
                ->add($entity['code'])
                ->addCellInt($entity['count'])
                ->addCell($this->getPercentCell($entity['percent_calculation']))
                ->addCellAmount($entity['items'])
                ->addCellAmount($entity['margin_amount'])
                ->addCell($this->getPercentCell($entity['margin_percent'], 0, true))
                ->addCellAmount($entity['total'])
                ->addCell($this->getPercentCell($entity['percent_amount']))
                ->endRow();
            $link = $this->getURL($entity['id']);
            $this->link($x, $y, $width, $this->getY() - $y, $link);
        }
        $this->currentEntity = null;

        // totals
        $totals = $this->getStateTotals($entities);
        $table->startHeaderRow()
            ->addCellTrans('calculation.fields.total')
            ->addCellInt($totals['calculation_count'])
            ->addCell($this->getPercentCell($totals['calculation_percent'], bold: true))
            ->addCellAmount($totals['items_amount'])
            ->addCellAmount($totals['margin_amount'])
            ->addCell($this->getPercentCell($totals['margin_percent'], 0, bold: true, roundingMode: \NumberFormatter::ROUND_DOWN))
            ->addCellAmount($totals['total_amount'])
            ->addCell($this->getPercentCell($totals['total_percent'], bold: true))
            ->endRow();
    }
}
