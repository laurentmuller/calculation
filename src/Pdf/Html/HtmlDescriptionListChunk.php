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
 * Represents a description list chunk.
 */
class HtmlDescriptionListChunk extends HtmlParentChunk
{
    public function __construct(?HtmlParentChunk $parent = null, ?string $className = null)
    {
        parent::__construct(HtmlTag::DESCRIPTION_LIST, $parent, $className);
    }

    /**
     * Adds a child to this collection of children.
     *
     * Do nothing if the child is already in this collection,
     * or if the child tag is not an instance of description term or description detail.
     */
    #[\Override]
    public function add(AbstractHtmlChunk $child): static
    {
        $tag = $child->getTag();
        if (HtmlTag::DESCRIPTION_TERM === $tag || HtmlTag::DESCRIPTION_DETAIL === $tag) {
            parent::add($child);
        }

        return $this;
    }
}
