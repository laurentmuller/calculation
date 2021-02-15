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
 * The zoom view enumeration.
 *
 * @method static Zoom DEFAULT()    Uses viewer default mode.
 * @method static Zoom FULL_PAGE()  Displays the entire page on screen.
 * @method static Zoom FULL_WIDTH() Uses maximum width of window.
 * @method static Zoom REAL()       Uses real size (equivalent to 100% zoom).
 *
 * @author Laurent Muller
 */
class Zoom extends Enum
{
    /**
     * Uses viewer default mode.
     */
    private const DEFAULT = 'default';

    /**
     * Displays the entire page on screen.
     */
    private const FULL_PAGE = 'fullpage';

    /**
     * Uses maximum width of window.
     */
    private const FULL_WIDTH = 'fullwidth';

    /**
     * Uses real size (equivalent to 100% zoom).
     */
    private const REAL = 'real';
}
