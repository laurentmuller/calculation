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

namespace App\Pdf\Html;

/**
 * The specialized chunk for HTML line break (br).
 */
class HtmlBrChunk extends AbstractHtmlChunk
{
    #[\Override]
    public function isNewLine(): bool
    {
        return true;
    }
}
