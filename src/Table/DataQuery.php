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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contains the data query parameters.
 */
class DataQuery
{
    /**
     * The page index (first = 1).
     */
    public readonly int $page;

    public function __construct(
        /* The callback state (XMLHttpRequest). */
        public bool $callback = false,
        /** The selected identifier. */
        #[Assert\GreaterThanOrEqual(0)]
        public readonly int $id = 0,
        /** The view. */
        public readonly TableView $view = TableView::TABLE,
        /** The position of the first result to retrieve (the "offset"). */
        #[Assert\GreaterThanOrEqual(0)]
        public readonly int $offset = 0,
        /** The maximum number of results to retrieve (the "limit"). */
        #[Assert\GreaterThanOrEqual(1)]
        public int $limit = 20,
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
    ) {
        $this->page = 1 + \intdiv($this->offset, $this->limit);
    }

    /**
     * Returns if the values must be shown as custom.
     */
    public function isViewCustom(): bool
    {
        return TableView::CUSTOM === $this->view;
    }
}
