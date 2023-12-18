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
class DataQuery
{
    public function __construct(
        /** The callback state (XMLHttpRequest). */
        public bool $callback = false,
        /** The selected identifier. */
        #[Assert\PositiveOrZero]
        public readonly int $id = 0,
        /** The view. */
        public TableView $view = TableView::TABLE,
        /** The position of the first result to retrieve (the "offset"). */
        #[Assert\PositiveOrZero]
        public readonly int $offset = 0,
        /** The maximum number of results to retrieve (the "limit"). */
        #[Assert\PositiveOrZero]
        public int $limit = 0,
        /** The search term. */
        #[Assert\NotNull]
        public readonly string $search = '',
        /** The sorted field. */
        #[Assert\NotNull]
        public string $sort = '',
        #[Assert\Choice([SortModeInterface::SORT_ASC, SortModeInterface::SORT_DESC])]
        /**
         * The sort order ('asc' or 'desc').
         *
         * @var SortModeInterface::*
         */
        public string $order = SortModeInterface::SORT_ASC,
        /** The custom data parameters. */
        #[Assert\Valid]
        public readonly DataParams $customData = new DataParams(),
        /** The cookie prefix */
        #[Assert\NotNull]
        public string $prefix = ''
    ) {
    }

    /**
     * Gets this values as attributes.
     *
     * @psalm-return array<string, bool|int|string>
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
     * Get the page index (first = 1).
     */
    public function getPage(): int
    {
        return 1 + \intdiv($this->offset, $this->limit);
    }

    /**
     * Returns if the view must be shown as custom.
     */
    public function isCustomView(): bool
    {
        return TableView::CUSTOM === $this->view;
    }

    /**
     * Gets this values as parameters.
     *
     * @psalm-return array<string, bool|int|string>
     */
    public function parameters(): array
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
