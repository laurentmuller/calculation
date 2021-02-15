<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Pdf\Enum;

use MyCLabs\Enum\Enum;

/**
 * The draw rectangle enumeration.
 *
 * @method static DrawRectangle BORDER() Draw the border around the rectangle.
 * @method static DrawRectangle BOTH()   Draw the border and fill the rectangle.
 * @method static DrawRectangle FILL()   Fill the rectangle.
 *
 * @author Laurent Muller
 */
class DrawRectangle extends Enum
{
    /**
     * Draw the border around the rectangle.
     */
    private const BORDER = 'D';

    /**
     * Draw the border and fill the rectangle.
     */
    private const BOTH = 'FD';

    /**
     * Fill the rectangle.
     */
    private const FILL = 'F';
}
