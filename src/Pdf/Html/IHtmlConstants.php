<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Pdf\Html;

/**
 * The HTML constants; mainly the tag names.
 *
 * @author Laurent Muller
 */
interface IHtmlConstants
{
    /**
     * The H1 tag name.
     */
    const H1 = 'h1';

    /**
     * The H2 tag name.
     */
    const H2 = 'h2';

    /**
     * The H3 tag name.
     */
    const H3 = 'h3';

    /**
     * The H4 tag name.
     */
    const H4 = 'h4';

    /**
     * The H5 tag name.
     */
    const H5 = 'h5';

    /**
     * The H6 tag name.
     */
    const H6 = 'h6';

    /**
     * The list item tag name.
     */
    const LIST_ITEM = 'li';

    /**
     * The ordered list tag name.
     */
    const LIST_ORDERED = 'ol';

    /**
     * The unordered list tag name.
     */
    const LIST_UNORDERED = 'ul';

    /**
     * The page break class name.
     */
    const PAGE_BREAK = 'page-break';

    /**
     * The paragraph tag name.
     */
    const PARAGRAPH = 'p';

    /**
     * The text chunk.
     */
    const TEXT = '#text';
}
