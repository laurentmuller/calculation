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
use App\Pdf\Interfaces\PdfColorInterface;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use App\Utils\FormatUtils;

/**
 * Report outputting HTML color names.
 *
 * @psalm-type ColorType = HtmlColorName|HtmlBootstrapColor|HtmlGrayedColor
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
        \usort($colors, $this->compareColors(...));
        $this->outputTable('Html by Value', $colors);

        $this->addPage();
        $colors = HtmlBootstrapColor::cases();
        $this->outputTable('Bootstrap by Name', $colors);

        $this->moveY(self::LINE_HEIGHT);
        \usort($colors, $this->compareColors(...));
        $this->outputTable('Bootstrap by Value', $colors, true);

        $this->moveY(self::LINE_HEIGHT);
        $colors = HtmlGrayedColor::cases();
        $this->outputTable('Grayed by Name', $colors);

        $this->moveY(self::LINE_HEIGHT);
        \usort($colors, $this->compareColors(...));
        $this->outputTable('Grayed by Value', $colors, true);

        return true;
    }

    private function compareColors(PdfColorInterface $color1, PdfColorInterface $color2): int
    {
        return \hexdec($color1->getPhpOfficeColor()) <=> \hexdec($color2->getPhpOfficeColor());
    }

    /**
     * @param array<ColorType> $colors
     */
    private function outputTable(string $title, array $colors, bool $currentY = false): void
    {
        $this->addBookmark(text: $title, currentY: $currentY);
        $table = PdfGroupTable::instance($this)
            ->addColumns(
                PdfColumn::left('Name', 40),
                PdfColumn::center('Hexadecimal', 30, true),
                PdfColumn::center('Red', 18, true),
                PdfColumn::center('Green', 18, true),
                PdfColumn::center('Blue', 18, true),
                PdfColumn::center('Color', 30, true),
            )
            ->outputHeaders()
            ->setGroupKey($title);

        $colorStyle = PdfStyle::getCellStyle();
        $colorCell = new PdfCell(style: $colorStyle);

        foreach ($colors as $color) {
            $rgb = $color->asRGB();
            $colorStyle->setFillColor($color->getFillColor());
            $table->addRow(
                $color->name,
                $color->value,
                (string) $rgb[0],
                (string) $rgb[1],
                (string) $rgb[2],
                $colorCell,
            );
        }

        $text = \sprintf('%d colors', FormatUtils::formatInt($colors));
        $table->singleLine($text, PdfStyle::getHeaderStyle());
    }
}
