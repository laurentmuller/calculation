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
 * A specialized chunk for HTML unordered list (ul).
 */
class HtmlUlChunk extends AbstractHtmlListChunk
{
    public function __construct(?HtmlParentChunk $parent = null, ?string $className = null)
    {
        parent::__construct(HtmlTag::LIST_UNORDERED, $parent, $className);
    }

    #[\Override]
    public function getBulletText(HtmlLiChunk $chunk): string
    {
        return $this->getLastBulletText();
    }

    #[\Override]
    public function getLastBulletText(): string
    {
        return \chr(149);
    }
}
