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
use App\Interfaces\TableInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contains the data query parameters.
 */
class DataQuery implements SortModeInterface
{
    /** The callback state (XMLHttpRequest). */
    public bool $callback = false;

    /** The selected identifier. */
    #[Assert\PositiveOrZero]
    public int $id = 0;

    /** The maximum number of results to retrieve (the "limit"). */
    #[Assert\PositiveOrZero]
    public int $limit = 0;

    /** The position of the first result to retrieve (the "offset"). */
    #[Assert\PositiveOrZero]
    public int $offset = 0;

    /**
     * The sort order ('asc' or 'desc').
     *
     * @phpstan-var self::SORT_*
     */
    #[Assert\Choice(choices: [self::SORT_ASC, self::SORT_DESC])]
    public string $order = self::SORT_ASC;

    /** @var array<string, int|string> */
    public array $parameters = [];

    /** The cookie prefix. */
    #[Assert\NotNull]
    public string $prefix = '';

    /** The search term. */
    #[Assert\NotNull]
    public string $search = '';

    /** The sorted field. */
    #[Assert\NotNull]
    public string $sort = '';

    /** The view. */
    public TableView $view = TableView::TABLE;

    /**
     * Add a parameter to this list of parameters.
     */
    public function addParameter(string $key, string|int $value): self
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * Gets values as attributes.
     *
     * @return array<string, bool|int|string>
     */
    public function attributes(): array
    {
        return [
            'search' => true,
            'search-text' => $this->search,
            'page-size' => $this->limit,
            'page-number' => $this->getPage(),
            'sort-name' => $this->sort,
            'sort-order' => $this->order,
            'custom-view-default-view' => $this->isCustomView(),
        ];
    }

    /**
     * Gets a parameter value as integer.
     */
    public function getIntParameter(string $key, int $default = 0): int
    {
        return (int) ($this->parameters[$key] ?? $default);
    }

    /**
     * Get the page index (first = 1).
     */
    public function getPage(): int
    {
        if (0 === $this->limit) {
            return 1;
        }

        return 1 + \intdiv($this->offset, $this->limit);
    }

    /**
     * Gets a parameter value as string.
     */
    public function getStringParameter(string $key, string $default = ''): string
    {
        return (string) ($this->parameters[$key] ?? $default);
    }

    /**
     * Returns if the view must be shown as custom.
     */
    public function isCustomView(): bool
    {
        return TableView::CUSTOM === $this->view;
    }

    /**
     * Gets values as parameters.
     *
     * @return array<string, bool|int|string>
     */
    public function params(): array
    {
        return [
            TableInterface::PARAM_ID => $this->id,
            TableInterface::PARAM_SEARCH => $this->search,
            TableInterface::PARAM_SORT => $this->sort,
            TableInterface::PARAM_ORDER => $this->order,
            TableInterface::PARAM_OFFSET => $this->offset,
            TableInterface::PARAM_VIEW => $this->view->value,
            TableInterface::PARAM_LIMIT => $this->limit,
        ];
    }
}
