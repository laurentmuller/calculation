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
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfChartLegendTrait;
use App\Pdf\Traits\PdfPieChartTrait;
use App\Table\CalculationTable;
use App\Traits\MathTrait;
use App\Traits\StateTotalsTrait;
use App\Utils\FormatUtils;
use fpdf\PdfRectangleStyle;
use fpdf\PdfTextAlignment;
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
    use MathTrait;
    use PdfChartLegendTrait;
    use PdfPieChartTrait;
    use StateTotalsTrait;

    /** @psalm-var QueryCalculationType|null */
    private ?array $currentRow = null;
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
        $this->setTitle($this->trans('chart.state.title'));
        $this->minMargin = $controller->getMinMargin();
    }

    public function drawCellText(PdfCellTextEvent $event): bool
    {
        if (0 !== $event->index || null === $this->currentRow) {
            return false;
        }
        if (!$this->applyFillColor($this->currentRow)) {
            return false;
        }

        $this->drawStateRect($event);
        $this->drawStateText($event);

        return true;
    }

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

    private function createTable(): PdfTable
    {
        return PdfTable::instance($this)
            ->setHeadersListener($this)
            ->setTextListener($this)
            ->addColumns(
                PdfColumn::left($this->trans('calculation.fields.state'), 20),
                PdfColumn::right($this->trans('calculation.list.title'), 18, true),
                PdfColumn::right('', 18, true),
                PdfColumn::right($this->trans('calculationgroup.fields.amount'), 24, true),
                PdfColumn::right($this->trans('calculation.fields.margin'), 24, true),
                PdfColumn::right('', 14, true),
                PdfColumn::right($this->trans('calculation.fields.total'), 24, true),
                PdfColumn::right('', 18, true)
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
    private function formatPercent(
        float $value,
        int $decimals = 2,
        bool $useStyle = false,
        bool $bold = false,
        int $roundingMode = \NumberFormatter::ROUND_HALFDOWN
    ): PdfCell {
        $style = $bold ? PdfStyle::getHeaderStyle() : PdfStyle::getCellStyle();
        $cell = new PdfCell(FormatUtils::formatPercent($value, true, $decimals, $roundingMode));
        if ($useStyle && $this->isMinMargin($value)) {
            $style->setTextColor(PdfTextColor::red());
        }
        $cell->setStyle($style);

        return $cell;
    }

    private function getURL(int $id): string
    {
        return $this->generator->generate('calculation_table', [CalculationTable::PARAM_STATE => $id]);
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
        $top = $this->topMargin + $this->getHeader()->getHeight() + self::LINE_HEIGHT;
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
            $this->currentRow = $entity;
            $table->addRow(
                $entity['code'],
                FormatUtils::formatInt($entity['count']),
                $this->formatPercent($entity['percent_calculation']),
                FormatUtils::formatAmount($entity['items']),
                FormatUtils::formatAmount($entity['margin_amount']),
                $this->formatPercent($entity['margin_percent'], 0, true),
                FormatUtils::formatAmount($entity['total']),
                $this->formatPercent($entity['percent_amount'])
            );
            /** @psalm-var non-empty-string $link */
            $link = $this->getURL($entity['id']);
            $this->link($x, $y, $width, $this->getY() - $y, $link);
        }
        $this->currentRow = null;

        // totals
        $totals = $this->getStateTotals($entities);
        $table->addHeaderRow(
            $this->trans('calculation.fields.total'),
            FormatUtils::formatInt($totals['calculation_count']),
            $this->formatPercent($totals['calculation_percent'], bold: true),
            FormatUtils::formatAmount($totals['items_amount']),
            FormatUtils::formatAmount($totals['margin_amount']),
            $this->formatPercent($totals['margin_percent'], 0, bold: true, roundingMode: \NumberFormatter::ROUND_DOWN),
            FormatUtils::formatAmount($totals['total_amount']),
            $this->formatPercent($totals['total_percent'], bold: true)
        );
    }
}
