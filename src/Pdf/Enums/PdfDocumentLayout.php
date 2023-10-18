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

use App\Interfaces\EnumDefaultInterface;
use App\Traits\EnumDefaultTrait;
use Elao\Enum\Attribute\EnumCase;

/**
 * The PDF display layout enumeration.
 *
 * @implements EnumDefaultInterface<PdfDocumentLayout>
 */
enum PdfDocumentLayout: string implements EnumDefaultInterface
{
    use EnumDefaultTrait;

    /**
     * Displays pages continuously.
     */
    case CONTINUOUS = 'continuous';

    /**
     * Uses viewer default mode.
     */
    case DEFAULT = 'default';

    /**
     * Displays one page at once (default).
     */
    #[EnumCase(extras: [EnumDefaultInterface::NAME => true])]
    case SINGLE = 'single';

    /**
     * Displays two pages on two columns.
     */
    case TWO_PAGES = 'two';
}
