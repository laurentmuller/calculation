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
use App\Pdf\Enums\PdfCellImagePosition;
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

    /**
     * Creates a boolean cell.
     *
     * @param bool                  $enabled       the enabled value used for icon and style
     * @param ?string               $text          the cell text
     * @param positive-int          $cols          the cell columns span
     * @param ?PdfBorder            $border        the cell border or null to use the default cell border (all borders)
     * @param ?PdfCellImagePosition $imagePosition the image position or null to use the default (left)
     */
    protected function getBooleanCell(
        bool $enabled,
        ?string $text = null,
        int $cols = 1,
        ?PdfBorder $border = null,
        ?PdfCellImagePosition $imagePosition = null
    ): PdfCell {
        $key = $this->getBooleanKey($enabled, $text, $cols, $border);
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
            style: $style,
            imagePosition: $imagePosition
        ) ?? PdfCell::instance(text: $text, cols: $cols, style: $style);

        return $this->booleanCells[$key] = $cell;
    }

    /**
     * Gets the style for the given boolean value.
     */
    protected function getBooleanStyle(bool $enabled, ?PdfBorder $border = null): PdfStyle
    {
        $style = PdfStyle::getCellStyle();
        if (!$enabled) {
            $style->setTextColor(PdfTextColor::darkGray());
        }
        if ($border instanceof PdfBorder) {
            $style->setBorder($border);
        }

        return $style;
    }

    private function getBooleanColor(bool $enabled): string
    {
        return ($enabled ? HtmlBootstrapColor::SUCCESS : PdfTextColor::darkGray())->asHex('#');
    }

    private function getBooleanIcon(bool $enabled): FontAwesomeIcon
    {
        return new FontAwesomeIcon(FontAwesomePath::SOLID, $enabled ? 'check' : 'xmark');
    }

    private function getBooleanKey(bool $enabled, ?string $text, int $cols, ?PdfBorder $border): string
    {
        $borderKey = (int) $border?->left << 0
            | (int) $border?->top << 1
            | (int) $border?->right << 2
            | (int) $border?->bottom << 3;

        return \sprintf('%d-%s-%d-%d', $enabled, $text, $cols, $borderKey);
    }
}
