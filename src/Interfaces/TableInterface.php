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

namespace App\Interfaces;

/**
 * Contains constants for Bootstrap tables.
 */
interface TableInterface
{
    /** The allowed page sizes. */
    public const array PAGE_LIST = [5, 10, 15, 20, 25, 30, 50];

    /** The column's parameter name (array of columns). */
    public const string PARAM_COLUMNS = 'columns';

    /** The identifier parameter name (int). */
    public const string PARAM_ID = 'id';

    /** The limit parameter name (int). */
    public const string PARAM_LIMIT = 'limit';

    /** The offset parameter name (int). */
    public const string PARAM_OFFSET = 'offset';

    /** The order parameter name (string). */
    public const string PARAM_ORDER = 'order';

    /** The page parameter name (int). */
    public const string PARAM_PAGE = 'page';

    /** The page lists parameter name (array of integers). */
    public const string PARAM_PAGE_LIST = 'pageList';

    /** The row's parameter name (array). */
    public const string PARAM_ROWS = 'rows';

    /** The search parameter name (string). */
    public const string PARAM_SEARCH = 'search';

    /** The sort parameter name (string). */
    public const string PARAM_SORT = 'sort';

    /** The total parameter name (int). */
    public const string PARAM_TOTAL = 'total';

    /** The total not filtered parameter name (int). */
    public const string PARAM_TOTAL_NOT_FILTERED = 'totalNotFiltered';

    /**
     * The display view parameter name (string: 'table' or 'custom').
     *
     * @see TableView
     */
    public const string PARAM_VIEW = 'view';
}
