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

namespace App\Table;

use App\Enums\TableView;
use App\Interfaces\SortModeInterface;

/**
 * Contains the data query parameters.
 *
 * @author Laurent Muller
 */
class DataQuery implements SortModeInterface
{
    /**
     * The callback state (XMLHttpRequest).
     */
    public bool $callback = false;

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
    public int $limit;

    /**
     * The position of the first result to retrieve (the "offset").
     */
    public int $offset = 0;

    /**
     * The sort order ('asc' or 'desc').
     */
    public string $order = self::SORT_ASC;

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
     * The view ('table', 'card' or 'custom').
     */
    public TableView $view;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->view = TableView::TABLE;
        $this->limit = $this->view->getPageSize();
    }

    /**
     * Adds a custom data to this list of custom datas.
     *
     * @param string $name  the custom data name
     * @param mixed  $value custom data value
     */
    public function addCustomData(string $name, mixed $value): self
    {
        $this->customData[$name] = $value;

        return $this;
    }

    /**
     * Gets a custom data.
     *
     * @param string     $name    the custom data name to get value for
     * @param mixed|null $default the default value to return if the custom data is not present
     *
     * @return mixed the custom data, if present; the default value otherwise
     */
    public function getCustomData(string $name, mixed $default = null): mixed
    {
        return $this->customData[$name] ?? $default;
    }

    /**
     * Returns if the values must be show as card.
     */
    public function isViewCard(): bool
    {
        return TableView::CARD === $this->view;
    }

    /**
     * Returns if the values must be show as custom.
     */
    public function isViewCustom(): bool
    {
        return TableView::CUSTOM === $this->view;
    }

    /**
     * Returns if the values must be show as table.
     */
    public function isViewTable(): bool
    {
        return TableView::TABLE === $this->view;
    }
}
