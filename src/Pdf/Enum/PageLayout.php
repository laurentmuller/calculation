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
 * The display page layout enumeration.
 *
 * @method static PageLayout CONTINOUS() Displays pages continuously.
 * @method static PageLayout DEFAULT()   Uses viewer default mode.
 * @method static PageLayout SINGLE()    Displays one page at once.
 * @method static PageLayout TWO_PAGES() Displays two pages on two columns.
 *
 * @author Laurent Muller
 */
class PageLayout extends Enum
{
    /**
     * Displays pages continuously.
     */
    private const CONTINOUS = 'continuous';

    /**
     * Uses viewer default mode.
     */
    private const DEFAULT = 'default';

    /**
     * Displays one page at once.
     */
    private const SINGLE = 'single';

    /**
     * Displays two pages on two columns.
     */
    private const TWO_PAGES = 'two';
}
