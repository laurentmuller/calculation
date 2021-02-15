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
 * Border style enumeration.
 *
 * @method static Border ALL()       Draw a border on all four sides.
 * @method static Border BOTTOM()    Draw a border on the bottom side.
 * @method static Border INHERITED() Inherit border from parent.
 * @method static Border LEFT()      Draw a border on the left side.
 * @method static Border NONE()      No border is draw.
 * @method static Border RIGHT()     Draw a border on the right side.
 * @method static Border TOP()       Draw a border on the top side.
 *
 * @author Laurent Muller
 */
class Border extends Enum
{
    /**
     * Draw a border on all four sides.
     */
    private const ALL = 1;

    /**
     * Draw a border on the bottom side.
     */
    private const BOTTOM = 'B';

    /**
     * Inherit border from parent.
     */
    private const INHERITED = -1;

    /**
     * Draw a border on the left side.
     */
    private const LEFT = 'L';

    /**
     * No border is draw.
     */
    private const NONE = 0;

    /**
     * Draw a border on the right side.
     */
    private const RIGHT = 'R';

    /**
     * Draw a border on the top side.
     */
    private const TOP = 'T';
}
