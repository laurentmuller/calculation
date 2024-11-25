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

namespace App\Tests\Data;

use App\Pdf\Traits\PdfMemoryImageTrait;
use fpdf\PdfDocument;

/**
 * Image document for tests purposes.
 */
class PdfImageDocument extends PdfDocument
{
    use PdfMemoryImageTrait;
}
