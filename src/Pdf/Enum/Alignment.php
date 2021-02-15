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
 * The text alignment enumeration.
 *
 * @method static Alignment CENTER()    Center alignment.
 * @method static Alignment INHERITED() Inherited alignment.
 * @method static Alignment JUSTIFIED() Justified alignment.
 * @method static Alignment LEFT()      Left alignment.
 * @method static Alignment RIGHT()     Right alignment.
 *
 * @author Laurent Muller
 */
class Alignment extends Enum
{
    /**
     * Center alignment.
     */
    private const CENTER = 'C';

    /**
     * Inherited alignment.
     */
    private const INHERITED = '';

    /**
     * Justified alignment.
     */
    private const JUSTIFIED = 'J';

    /**
     * Left alignment.
     */
    private const LEFT = 'L';

    /**
     * Right alignment.
     */
    private const RIGHT = 'R';
}
