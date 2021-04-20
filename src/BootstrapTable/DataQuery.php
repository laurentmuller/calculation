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

namespace App\BootstrapTable;

/**
 * Contains the data query parameters.
 *
 * @author Laurent Muller
 */
class DataQuery
{
    /**
     * The callback state (XMLHttpRequest).
     */
    public bool $callback = false;

    /**
     * The card view state.
     */
    public bool $card = false;

    /**
     * The custom datas.
     */
    public array $customData = [];

    /**
     * The selected identifier.
     */
    public int $id = 0;

    /**
     * The maximum number of results to retrieve (the "limit").
     */
    public int $limit = 20;

    /**
     * The position of the first result to retrieve (the "offset").
     */
    public int $offset = 0;

    /**
     * The sort order ('asc' or 'desc').
     */
    public string $order = Column::SORT_ASC;

    /**
     * The page index (first = 1).
     */
    public int $page = 1;

    /**
     * The search term.
     */
    public string $search = '';

    /**
     * The sorted field.
     */
    public string $sort = '';

    /**
     * The view (null, 'card' or 'custom').
     */
    public ?string $view = null;

    /**
     * Adds a custom data to this list of custom datas.
     *
     * @param string $name  the custom data name
     * @param mixed  $value custom data value
     */
    public function addCustomData(string $name, $value): self
    {
        $this->customData[$name] = $value;

        return $this;
    }

    /**
     * Gets a custom data.
     *
     * @param string $name    the custom data name to get value for
     * @param mixed  $default the default value to returns if the custom data is not present
     *
     * @return mixed the custom data, if present; the default value otherwise
     */
    public function getCustomData(string $name, $default = null)
    {
        return $this->customData[$name] ?? $default;
    }
}
