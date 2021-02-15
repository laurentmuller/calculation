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
 * The document output mode enumeration.
 *
 * @method static OutputMode DOWNLOAD() Send to the browser and force a file download with the given name parameter.
 * @method static OutputMode FILE()     Save to a local file with the given name parameter (may include a path).
 * @method static OutputMode INLINE()   Send the file inline to the browser (default). The PDF viewer is used if available.
 * @method static OutputMode STRING()   Return the document as a string.
 *
 * @author Laurent Muller
 */
class OutputMode extends Enum
{
    /**
     * Send to the browser and force a file download with the given name parameter.
     */
    private const DOWNLOAD = 'D';

    /**
     * Save to a local file with the given name parameter (may include a path).
     */
    private const FILE = 'F';

    /**
     * Send the file inline to the browser (default).
     * The PDF viewer is used if available.
     */
    private const INLINE = 'I';

    /**
     * Return the document as a string.
     */
    private const STRING = 'S';
}
