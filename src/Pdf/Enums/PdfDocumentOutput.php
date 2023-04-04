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
 * The PDF document output enumeration.
 *
 * @implements EnumDefaultInterface<PdfDocumentOutput>
 */
enum PdfDocumentOutput: string implements EnumDefaultInterface
{
    use EnumDefaultTrait;

    /*
     * Send to the browser and force a file download with the given name parameter.
     */
    case DOWNLOAD = 'D';

    /*
     * Save to a local file with the given name parameter (may include a path).
     */
    case FILE = 'F';

    /*
     * Send the file inline to the browser (default).
     *
     * The PDF viewer is used if available.
     */
    #[EnumCase(extras: [EnumDefaultInterface::NAME => true])]
    case INLINE = 'I';

    /*
     * Return the document as a string.
     */
    case STRING = 'S';
}
