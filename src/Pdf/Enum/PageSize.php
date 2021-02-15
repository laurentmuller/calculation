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
 * The page size enumeration.
 *
 * @method static PageSize A3()     The A3 document size.
 * @method static PageSize A4()     The A4 document size.
 * @method static PageSize A5()     The A5 document size.
 * @method static PageSize LEGAL()  The Legal document size.
 * @method static PageSize LETTER() The Letter document size.
 *
 * @author Laurent Muller
 */
class PageSize extends Enum
{
    /**
     * The A3 document size.
     */
    private const A3 = 'A3';

    /**
     * The A4 document size.
     */
    private const A4 = 'A4';

    /**
     * The A5 document size.
     */
    private const A5 = 'A5';

    /**
     * The Legal document size.
     */
    private const LEGAL = 'Legal';

    /**
     * The Letter document size.
     */
    private const LETTER = 'Letter';
}
