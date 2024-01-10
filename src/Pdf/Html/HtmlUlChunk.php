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
    public function getBulletLast(): string
    {
        return \chr(149);
    }

    public function getBulletText(HtmlLiChunk $chunk): string
    {
        return $this->getBulletLast();
    }
}
