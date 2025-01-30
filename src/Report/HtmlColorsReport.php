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

use App\Pdf\Html\HtmlBootstrapColor;
use App\Pdf\Html\HtmlColorName;
use App\Pdf\Html\HtmlGrayedColor;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use App\Utils\FormatUtils;
use fpdf\Enums\PdfFontName;

/**
 * Report outputting HTML color names.
 */
class HtmlColorsReport extends AbstractReport
{
    public function render(): bool
    {
        $this->setTitle('Colors');

        $this->addPage();
        $colors = HtmlColorName::cases();
        $this->outputTable('Html by Name', $colors);

        $this->addPage();
        $this->sortColors($colors);
        $this->outputTable('Html by Value', $colors);

        $this->addPage();
        $colors = HtmlBootstrapColor::cases();
        $this->outputTable('Bootstrap by Name', $colors);

        $this->sortColors($colors);
        $this->moveY(self::LINE_HEIGHT);
        $this->outputTable('Bootstrap by Value', $colors, true);

        $this->addPage();
        $colors = HtmlGrayedColor::cases();
        $this->outputTable('Grayed by Name', $colors);

        $this->sortColors($colors);
        $this->moveY(self::LINE_HEIGHT);
        $this->outputTable('Grayed by Value', $colors, true);

        return true;
    }

    /**
     * @param array<HtmlColorName|HtmlBootstrapColor|HtmlGrayedColor> $colors
     */
    private function outputTable(string $title, array $colors, bool $currentY = false): void
    {
        $this->addBookmark(text: $title, currentY: $currentY);
        $table = PdfGroupTable::instance($this)
            ->addColumns(
                PdfColumn::left('Name', 40),
                PdfColumn::center('Hexadecimal', 35, true),
                PdfColumn::center('RGB', 35, true),
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
                $colorCell,
            );
        }
        $text = \sprintf('%d colors', FormatUtils::formatInt($colors));
        $table->singleLine($text, PdfStyle::getHeaderStyle());
    }

    /**
     * @param array<HtmlColorName|HtmlBootstrapColor|HtmlGrayedColor> $colors
     */
    private function sortColors(array &$colors): void
    {
        \usort(
            $colors,
            fn (
                HtmlColorName|HtmlBootstrapColor|HtmlGrayedColor $color1,
                HtmlColorName|HtmlBootstrapColor|HtmlGrayedColor $color2
            ): int => \hexdec($color1->value) <=> \hexdec($color2->value)
        );
    }
}
