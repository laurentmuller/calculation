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

use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\Interfaces\PdfDrawCellTextInterface;
use App\Pdf\PdfBorder;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfDocument;
use App\Pdf\PdfFillColor;
use App\Pdf\PdfPieChartTrait;
use App\Pdf\PdfRectangle;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Pdf\PdfTextColor;
use App\Repository\CalculationStateRepository;
use App\Traits\MathTrait;
use App\Utils\FormatUtils;

/**
 * Report for calculations by states.
 *
 * @psalm-import-type QueryCalculationType from CalculationStateRepository
 *
 * @extends AbstractArrayReport<QueryCalculationType>
 */
class CalculationByStateReport extends AbstractArrayReport implements PdfDrawCellTextInterface
{
    use MathTrait;
    use PdfPieChartTrait;

    /** @psalm-var QueryCalculationType|null */
    private ?array $currentRow = null;
    private float $minMargin = 100.0;

    public function drawCellText(PdfTableBuilder $builder, int $index, PdfRectangle $bounds, string $text, PdfTextAlignment $align, float $height): bool
    {
        if (0 !== $index || null === $this->currentRow) {
            return false;
        }
        if (!$this->applyFillColor($this->currentRow)) {
            return false;
        }

        $parent = $builder->getParent();
        $this->drawStateRect($parent, $bounds);
        $this->drawStateText($parent, $bounds, $text, $align, $height);

        return true;
    }

    protected function doRender(array $entities): bool
    {
        $this->SetTitle($this->transChart('title_by_state'));
        $this->minMargin = $this->controller->getMinMargin();
        $this->AddPage();
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

    private function createTable(): PdfTableBuilder
    {
        return PdfTableBuilder::instance($this)
            ->setTextListener($this)
            ->addColumns(
                PdfColumn::left($this->transChart('fields.state'), 20),
                PdfColumn::right($this->transChart('fields.count'), 25, true),
                PdfColumn::right('%', 15, true),
                PdfColumn::right($this->transChart('fields.net'), 20, true),
                PdfColumn::right($this->transChart('fields.margin_amount'), 20, true),
                PdfColumn::right($this->transChart('fields.margin_percent'), 20, true),
                PdfColumn::right($this->transChart('fields.total'), 20, true),
                PdfColumn::right('%', 15, true)
            )->outputHeaders();
    }

    private function drawStateRect(PdfDocument $parent, PdfRectangle $bounds): void
    {
        $margin = $parent->getCellMargin();
        $parent->Rect(
            $bounds->x() + $margin,
            $bounds->y() + $margin,
            5.0,
            $bounds->height() - 2.0 * $margin,
            PdfBorder::BOTH
        );
    }

    private function drawStateText(PdfDocument $parent, PdfRectangle $bounds, string $text, PdfTextAlignment $align, float $height): void
    {
        $offset = 6.0;
        $parent->SetX($parent->GetX() + $offset);
        $parent->Cell(
            w: $bounds->width() - $offset,
            h: $height,
            txt: $text,
            align: $align
        );
    }

    private function formatPercent(float $value, int $decimals = 1, bool $useStyle = false, bool $bold = false): PdfCell
    {
        $style = $bold ? PdfStyle::getHeaderStyle() : PdfStyle::getCellStyle();
        $cell = new PdfCell(FormatUtils::formatPercent($value, false, $decimals, \NumberFormatter::ROUND_HALFEVEN));
        if ($useStyle && $this->isMinMargin($value)) {
            $style->setTextColor(PdfTextColor::red());
        }
        $cell->setStyle($style);

        return $cell;
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
        $top = $this->getTopMargin() + $this->getHeader()->getHeight() + self::LINE_HEIGHT;
        $radius = $this->GetPageWidth() / 4.0;
        $centerX = $this->GetPageWidth() / 2.0;
        $centerY = $top + $radius;
        $rows = \array_map(function (array $entity): array {
            return [
                'label' => $entity['code'],
                'color' => $entity['color'],
                'value' => $entity['percentAmount'],
            ];
        }, $entities);
        $this->pieChart($centerX, $centerY, $radius, $rows);
        $this->SetY($centerY + $radius + self::LINE_HEIGHT);
        $this->resetStyle();

        // for testing purpose
        $this->pieLegendHorizontal($rows);
        $this->pieLegendVertical($rows, $this->getLeftMargin(), $top);
        $this->ln();
    }

    /**
     * @psalm-param QueryCalculationType[] $entities
     */
    private function renderTable(array $entities): void
    {
        $table = $this->createTable();

        foreach ($entities as $entity) {
            $this->currentRow = $entity;
            $table->addRow(
                $entity['code'],
                FormatUtils::formatInt($entity['count']),
                $this->formatPercent($entity['percentCalculation'], 2),
                FormatUtils::formatInt($entity['items']),
                FormatUtils::formatInt($entity['marginAmount']),
                $this->formatPercent($entity['margin'], 0, true),
                FormatUtils::formatInt($entity['total']),
                $this->formatPercent($entity['percentAmount'], 2)
            );
            $this->currentRow = null;
        }

        // total
        $count = $this->sum($entities, 'count');
        $percentCalculation = $this->sum($entities, 'percentCalculation');
        $items = $this->sum($entities, 'items');
        $marginAmount = $this->sum($entities, 'marginAmount');
        $total = $this->sum($entities, 'total');
        $net = $total - $items;
        $margin = 1.0 + $this->safeDivide($net, $items);
        $percentAmount = $this->sum($entities, 'percentAmount');

        $table->addHeaderRow(
            $this->transChart('fields.total'),
            FormatUtils::formatInt($count),
            $this->formatPercent($percentCalculation, 2, bold: true),
            FormatUtils::formatInt($items),
            FormatUtils::formatInt($marginAmount),
            $this->formatPercent($margin, 0, bold: true),
            FormatUtils::formatInt($total),
            $this->formatPercent($percentAmount, 2, bold: true)
        );
    }

    private function sum(array $entities, string $key): float
    {
        return \array_sum(\array_column($entities, $key));
    }

    private function transChart(string $key): string
    {
        return $this->trans($key, [], 'chart');
    }
}
