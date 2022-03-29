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

namespace App\Pdf\Html;

/**
 * Specialized chunk for HTML unordered list (ul).
 *
 * @author Laurent Muller
 */
class HtmlUlChunk extends HtmlParentChunk
{
    /**
     * Constructor.
     *
     * @param string               $name   the tag name
     * @param HtmlParentChunk|null $parent the parent chunk
     */
    public function __construct(protected string $name, ?HtmlParentChunk $parent = null)
    {
        parent::__construct($name, $parent);
    }
}
