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

namespace App\Interfaces;

/**
 * Class implementing this interface provide function for download document.
 *
 * @author Laurent Muller
 */
interface MimeTypeInterface
{
    /**
     * Gets the mime type.
     */
    public function getMimeType(): string;
}
