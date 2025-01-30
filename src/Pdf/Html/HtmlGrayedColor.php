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

use App\Pdf\Interfaces\PdfColorInterface;
use App\Pdf\Traits\PdfColorTrait;

/**
 * Bootstrap grayed color enumeration.
 *
 * Picked from version 5.3.
 */
enum HtmlGrayedColor: string implements PdfColorInterface
{
    use PdfColorTrait;

    case Gray100 = '#F8F9FA';
    case Gray200 = '#E9ECEF';
    case Gray300 = '#DEE2E6';
    case Gray400 = '#CED4DA';
    case Gray500 = '#ADB5BD';
    case Gray600 = '#868E96';
    case Gray700 = '#495057';
    case Gray800 = '#343A40';
    case Gray900 = '#212529';
}
