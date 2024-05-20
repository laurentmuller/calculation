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
    public function __construct(
        /** The callback state (XMLHttpRequest). */
        public bool $callback = false,
        /** The selected identifier. */
        #[Assert\PositiveOrZero]
        public int $id = 0,
        /** The view. */
        public TableView $view = TableView::TABLE,
        /** The position of the first result to retrieve (the "offset"). */
        #[Assert\PositiveOrZero]
        public int $offset = 0,
        /** The maximum number of results to retrieve (the "limit"). */
        #[Assert\PositiveOrZero]
        public int $limit = 0,
        /** The search term. */
        #[Assert\NotNull]
        public string $search = '',
        /** The sorted field. */
        #[Assert\NotNull]
        public string $sort = '',
        /**
         * The sort order ('asc' or 'desc').
         *
         * @psalm-var self::SORT_*
         */
        #[Assert\Choice([self::SORT_ASC, self::SORT_DESC])]
        public string $order = self::SORT_ASC,
        /** The cookie prefix */
        #[Assert\NotNull]
        public string $prefix = '',
        /** The group identifier. */
        #[Assert\PositiveOrZero]
        public int $groupId = 0,
        /** The category identifier. */
        #[Assert\PositiveOrZero]
        public int $categoryId = 0,
        /** The calculation state identifier. */
        #[Assert\PositiveOrZero]
        public int $stateId = 0,
        /** The edit state identifier. */
        #[Assert\Range(min: -1, max: 1)]
        public int $stateEditable = 0,
        /** The log level. */
        #[Assert\NotNull]
        public string $level = '',
        /** The log channel. */
        #[Assert\NotNull]
        public string $channel = '',
        /** The search entity. */
        #[Assert\NotNull]
        public string $entity = '',
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
        if (0 === $this->limit) {
            return 1;
        }

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
