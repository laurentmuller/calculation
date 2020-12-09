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
 * The HTML constants; mainly the tag names.
 *
 * @author Laurent Muller
 */
interface HtmlConstantsInterface
{
    /**
     * The H1 tag name.
     */
    public const H1 = 'h1';

    /**
     * The H2 tag name.
     */
    public const H2 = 'h2';

    /**
     * The H3 tag name.
     */
    public const H3 = 'h3';

    /**
     * The H4 tag name.
     */
    public const H4 = 'h4';

    /**
     * The H5 tag name.
     */
    public const H5 = 'h5';

    /**
     * The H6 tag name.
     */
    public const H6 = 'h6';

    /**
     * The line break tag name.
     */
    public const LINE_BREAK = 'br';

    /**
     * The list item tag name.
     */
    public const LIST_ITEM = 'li';

    /**
     * The ordered list tag name.
     */
    public const LIST_ORDERED = 'ol';

    /**
     * The unordered list tag name.
     */
    public const LIST_UNORDERED = 'ul';

    /**
     * The page break class name.
     */
    public const PAGE_BREAK = 'page-break';

    /**
     * The paragraph tag name.
     */
    public const PARAGRAPH = 'p';

    /**
     * The samp tag name.
     */
    public const SAMP = 'samp';

    /**
     * The text chunk.
     */
    public const TEXT = '#text';
}
