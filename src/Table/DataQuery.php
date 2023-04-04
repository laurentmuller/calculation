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
 */
class DataQuery implements SortModeInterface
{
    /**
     * The callback state (XMLHttpRequest).
     */
    public bool $callback = false;

    /**
     * The custom datas.
     *
     * @var array<string, mixed>
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
     *
     * @psalm-var SortModeInterface::* $order
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
     * The view.
     */
    public TableView $view = TableView::TABLE;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->limit = TableView::TABLE->getPageSize();
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
     *
     * @psalm-template T
     *
     * @psalm-param T|null $default
     *
     * @psalm-return ($default is null ? (T|null) : T)
     */
    public function getCustomData(string $name, mixed $default = null): mixed
    {
        /** @psalm-var T|null $value */
        $value = $this->customData[$name] ?? $default;

        return $value;
    }

    /**
     * Returns if the values must be shown as custom.
     */
    public function isViewCustom(): bool
    {
        return TableView::CUSTOM === $this->view;
    }

    /**
     * Returns if the values must be shown as table.
     */
    public function isViewTable(): bool
    {
        return TableView::TABLE === $this->view;
    }

    /**
     * Sets the sorting order.
     */
    public function setOrder(string $order): self
    {
        $order = \strtolower($order);
        $this->order = match ($order) {
            self::SORT_ASC,
            self::SORT_DESC => $order,
            default => $this->order,
        };

        return $this;
    }
}
