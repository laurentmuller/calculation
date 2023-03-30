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

use App\Interfaces\DefaultEnumInterface;
use App\Traits\DefaultEnumTrait;
use Elao\Enum\Attribute\EnumCase;

/**
 * The PDF display layout enumeration.
 *
 * @implements DefaultEnumInterface<PdfDocumentLayout>
 */
enum PdfDocumentLayout: string implements DefaultEnumInterface
{
    use DefaultEnumTrait;

    /*
     * Displays pages continuously.
     */
    case CONTINUOUS = 'continuous';

    /*
     * Uses viewer default mode.
     */
    case DEFAULT = 'default';

    /*
     * Displays one page at once (default).
     */
    #[EnumCase(extras: [DefaultEnumInterface::NAME => true])]
    case SINGLE = 'single';

    /*
     * Displays two pages on two columns.
     */
    case TWO_PAGES = 'two';
}
