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

namespace App\Pdf\Traits;

use App\Enums\FontAwesomePath;
use App\Model\FontAwesomeIcon;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\Html\HtmlBootstrapColor;
use App\Pdf\PdfCell;
use App\Pdf\PdfStyle;
use fpdf\PdfBorder;

/**
 * Trait to create boolean cells with an icon.
 */
trait PdfBooleanCellTrait
{
    /** @var array <string, PdfCell> */
    private array $booleanCells = [];
    private ?PdfStyle $disabledStyle = null;

    /**
     * Creates a boolean cell.
     *
     * @param bool         $enabled the enabled value used for icon and style
     * @param string       $text    the cell text
     * @param positive-int $cols    the number of columns to span
     * @param ?PdfBorder   $border  the cell border or null to use the default cell border
     */
    protected function getBooleanCell(bool $enabled, string $text, int $cols = 1, ?PdfBorder $border = null): PdfCell
    {
        $key = \sprintf('%s-%d-%d', $text, (int) $enabled, (int) ($border instanceof PdfBorder));
        if (\array_key_exists($key, $this->booleanCells)) {
            return $this->booleanCells[$key];
        }

        $icon = $this->getBooleanIcon($enabled);
        $color = $this->getBooleanColor($enabled);
        $style = $this->getBooleanStyle($enabled, $border);
        $cell = $this->cellService->getCell(
            icon: $icon,
            color: $color,
            text: $text,
            cols: $cols,
            style: $style
        ) ?? PdfCell::instance(text: $text, cols: $cols, style: $style);

        return $this->booleanCells[$key] = $cell;
    }

    /**
     * Gets the style for the given boolean value.
     *
     * @return PdfStyle|null the style if the $enabled parameter is <code>false</code>, <code>null</code> otherwise
     */
    protected function getBooleanStyle(bool $enabled, ?PdfBorder $border = null): ?PdfStyle
    {
        $style = PdfStyle::getCellStyle();
        if ($border instanceof PdfBorder) {
            $style->setBorder($border);
        }
        if ($enabled) {
            return $style;
        }

        return $this->disabledStyle ??= clone $style
            ->setTextColor(PdfTextColor::darkGray());
    }

    private function getBooleanColor(bool $enabled): string
    {
        return ($enabled ? HtmlBootstrapColor::SUCCESS : PdfTextColor::darkGray())->asHex('#');
    }

    private function getBooleanIcon(bool $enabled): FontAwesomeIcon
    {
        return new FontAwesomeIcon(FontAwesomePath::SOLID, $enabled ? 'check' : 'xmark');
    }
}
