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
 * The document orientation enumeration.
 *
 * @method static PageOrientation LANDSCAPE() The document orientation as landscape.
 * @method static PageOrientation PORTRAIT()  The document orientation as portrait.
 *
 * @author Laurent Muller
 */
class PageOrientation extends Enum
{
    /**
     * The document orientation as landscape.
     */
    private const LANDSCAPE = 'L';

    /**
     * The document orientation as portrait.
     */
    private const PORTRAIT = 'P';
}
