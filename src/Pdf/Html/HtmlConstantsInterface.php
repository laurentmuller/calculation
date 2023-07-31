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
 * The HTML constants; mainly the tag names.
 */
interface HtmlConstantsInterface
{
    /**
     * The body tag name.
     */
    public const BODY = 'body';

    /**
     * The bold element.
     */
    public const BOLD = 'b';

    /**
     * The class attribute name.
     */
    public const CLASS_ATTRIBUTE = 'class';

    /**
     * The inline code element.
     */
    public const CODE = 'code';

    /**
     * The emphasis element.
     */
    public const EMPHASIS = 'em';

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
     * The italic element.
     */
    public const ITALIC = 'i';

    /**
     * The keyboard input element.
     */
    public const KEYBOARD = 'kbd';

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
     * The sample output element.
     */
    public const SAMPLE = 'samp';

    /**
     * The span tag name.
     */
    public const SPAN = 'span';

    /**
     * The start ordered list attribute name.
     */
    public const START_ATTRIBUTE = 'start';

    /**
     * The strong importance element.
     */
    public const STRONG = 'strong';

    /**
     * The text chunk.
     */
    public const TEXT = '#text';

    /**
     * The list type attribute name.
     */
    public const TYPE_ATTRIBUTE = 'type';

    /*
     * The underline element.
     */
    public const UNDERLINE = 'u';

    /**
     * The variable element.
     */
    public const VARIABLE = 'var';
}
