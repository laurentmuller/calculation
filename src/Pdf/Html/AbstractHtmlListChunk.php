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
 * Abstract chunk for HTML ordered (ol) or unordered (ul) list.
 *
 * Only child instance of HtmlLiChunk can be added.
 */
abstract class AbstractHtmlListChunk extends HtmlParentChunk
{
    /**
     * Gets the bullet text for the last child (if any).
     */
    abstract public function getBulletLast(): string;

    /**
     * Gets the bullet text for the given child.
     */
    abstract public function getBulletText(HtmlLiChunk $chunk): string;

    #[\Override]
    protected function isValidChild(AbstractHtmlChunk $child): bool
    {
        return parent::isValidChild($child) && $child instanceof HtmlLiChunk;
    }
}
