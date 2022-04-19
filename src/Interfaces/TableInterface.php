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
 *
 * @author Laurent Muller
 */
interface TableInterface
{
    /**
     * The allowed page sizes.
     */
    public const PAGE_LIST = [5, 10, 15, 20, 25, 30, 50];

    /**
     * The column's parameter name (array of columns).
     */
    public const PARAM_COLUMNS = 'columns';

    /**
     * The identifier parameter name (int).
     */
    public const PARAM_ID = 'id';

    /**
     * The limit parameter name (int).
     */
    public const PARAM_LIMIT = 'limit';

    /**
     * The offset parameter name (int).
     */
    public const PARAM_OFFSET = 'offset';

    /**
     * The order parameter name (string).
     */
    public const PARAM_ORDER = 'order';

    /**
     * The page parameter name (int).
     */
    public const PARAM_PAGE = 'page';

    /**
     * The page list parameter name (array of integers).
     */
    public const PARAM_PAGE_LIST = 'pageList';

    /**
     * The row's parameter name (array).
     */
    public const PARAM_ROWS = 'rows';

    /**
     * The search parameter name (string).
     */
    public const PARAM_SEARCH = 'search';

    /**
     * The sort parameter name (string).
     */
    public const PARAM_SORT = 'sort';

    /**
     * The total parameter name (int).
     */
    public const PARAM_TOTAL = 'total';

    /**
     * The total not filtered parameter name (int).
     */
    public const PARAM_TOTAL_NOT_FILTERED = 'totalNotFiltered';

    /**
     * The dispay view parameter name (string: 'table', 'custom' or 'card').
     */
    public const PARAM_VIEW = 'view';
}
