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
 * A specialized chunk for HTML description list chunk (dl).
 *
 * Only child instance of description term (dt) or description detail (dd) can be added.
 */
class HtmlDescriptionListChunk extends HtmlParentChunk
{
    public function __construct(?HtmlParentChunk $parent = null, ?string $className = null)
    {
        parent::__construct(HtmlTag::DESCRIPTION_LIST, $parent, $className);
    }

    #[\Override]
    protected function isValidChild(AbstractHtmlChunk $child): bool
    {
        return parent::isValidChild($child) && $child->is(HtmlTag::DESCRIPTION_TERM, HtmlTag::DESCRIPTION_DETAIL);
    }
}
