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

namespace App\Pdf\Enums;

use App\Pdf\Traits\PdfTransparencyTrait;
use Symfony\Component\String\UnicodeString;

/**
 * Transparency blend mode enumeration.
 *
 * @see PdfTransparencyTrait::setAlpha()
 */
enum PdfBlendMode
{
    case COLOR;
    case COLOR_BURN;
    case COLOR_DODGE;
    case DARKEN;
    case DIFFERENCE;
    case EXCLUSION;
    case HARD_LIGHT;
    case HUE;
    case LIGHTEN;
    case LUMINOSITY;
    case MULTIPLY;
    case NORMAL;
    case OVERLAY;
    case SATURATION;
    case SCREEN;
    case SOFT_LIGHT;

    /**
     * Convert this name to title camel case.
     */
    public function camel(): string
    {
        return (new UnicodeString($this->name))
            ->lower()
            ->camel()
            ->title()
            ->toString();
    }
}
