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

use App\Pdf\Html\HtmlColorName;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use fpdf\Enums\PdfFontName;

/**
 * Report to output HTML color names.
 *
 * @see HtmlColorName
 */
class HtmlColorNameReport extends AbstractReport
{
    public function render(): bool
    {
        $this->setTitle('HTML Color Names');

        $colors = HtmlColorName::cases();
        $this->addPage();
        $this->outputTable('Colors by Name', $colors);

        \usort($colors, fn (HtmlColorName $c1, HtmlColorName $c2): int => $c1->asInt() <=> $c2->asInt());
        $this->addPage();
        $this->outputTable('Colors by Value', $colors);

        return true;
    }

    /**
     * @psalm-param HtmlColorName[] $colors
     */
    private function outputTable(string $title, array $colors): void
    {
        $table = PdfGroupTable::instance($this)
            ->addColumns(
                PdfColumn::left('Name', 40),
                PdfColumn::center('Hexadecimal', 35, true),
                PdfColumn::right('RGB', 35, true),
                PdfColumn::right('Decimal', 35, true),
                PdfColumn::center('Color', 35, true),
            )->outputHeaders()
            ->setGroupKey($title);

        $colorStyle = PdfStyle::getCellStyle();
        $colorCell = new PdfCell(style: $colorStyle);
        $valueStyle = PdfStyle::getCellStyle()
            ->setFontName(PdfFontName::COURIER);

        foreach ($colors as $color) {
            $colorStyle->setFillColor($color->getFillColor());
            $table->addRow(
                $color->name,
                new PdfCell(text: $color->value, style: $valueStyle),
                \implode(', ', $color->asRGB()),
                (string) $color->asInt(),
                $colorCell,
            );
        }
        $text = \sprintf('%d colors', \count($colors));
        $table->singleLine($text, PdfStyle::getHeaderStyle());
    }
}
