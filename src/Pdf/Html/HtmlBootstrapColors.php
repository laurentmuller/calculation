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

namespace App\Pdf\Html;

use App\Pdf\PdfDrawColor;
use App\Pdf\PdfFillColor;
use App\Pdf\PdfTextColor;

/**
 * Bootstrap color enumeration.
 *
 * @version 4.4.1
 */
enum HtmlBootstrapColors: string
{
    case DANGER = '#DC3545';
    case DARK = '#343A40';
    case INFO = '#17A2B8';
    case LIGHT = '#F8F9FA';
    case PRIMARY = '#007BFF';
    case SECONDARY = '#6C757D';
    case SUCCESS = '#28A745';
    case WARNING = '#FFC107';

    public function getDrawColor(): PdfDrawColor
    {
        if (null === $color = PdfDrawColor::create($this->value)) {
            throw new \RuntimeException('Unable to create draw color.');
        }

        return $color;
    }

    public function getFillColor(): PdfFillColor
    {
        if (null === $color = PdfFillColor::create($this->value)) {
            throw new \RuntimeException('Unable to create fill color.');
        }

        return $color;
    }

    public function getTextColor(): PdfTextColor
    {
        if (null === $color = PdfTextColor::create($this->value)) {
            throw new \RuntimeException('Unable to create text color.');
        }

        return $color;
    }
}
