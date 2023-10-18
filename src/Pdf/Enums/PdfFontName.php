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
 * The PDF font name enumeration.
 *
 * @implements EnumDefaultInterface<PdfFontName>
 */
enum PdfFontName: string implements EnumDefaultInterface
{
    use EnumDefaultTrait;

    /**
     * The Arial font name (synonymous: sans serif).
     *
     * This is the default font.
     */
    #[EnumCase(extras: [EnumDefaultInterface::NAME => true])]
    case ARIAL = 'Arial';

    /**
     * The Courier font name (fixed-width).
     */
    case COURIER = 'Courier';

    /**
     * The Helvetica font name (synonymous: sans serif).
     */
    case HELVETICA = 'Helvetica';

    /**
     * The Symbol font name (symbolic).
     */
    case SYMBOL = 'Symbol';

    /**
     * The Times font name (serif).
     */
    case TIMES = 'Times';

    /**
     * The ZapfDingbats font name (symbolic).
     */
    case ZAPFDINGBATS = 'ZapfDingbats';
}
