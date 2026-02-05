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

use App\Entity\GlobalMargin;
use App\Repository\GlobalMarginRepository;
use App\Service\IndexService;
use Symfony\Component\Filesystem\Path;

/**
 * The global margins table.
 *
 * @extends AbstractEntityTable<GlobalMargin, GlobalMarginRepository>
 */
class GlobalMarginTable extends AbstractEntityTable
{
    public function __construct(
        GlobalMarginRepository $repository,
        private readonly IndexService $indexService
    ) {
        parent::__construct($repository);
    }

    #[\Override]
    protected function count(): int
    {
        return $this->indexService->getCatalog()['globalMargin'];
    }

    #[\Override]
    protected function getColumnDefinitions(): string
    {
        return Path::join(__DIR__, 'Definition', 'global_margin.json');
    }

    #[\Override]
    protected function updateDataQuery(DataQuery $query): void
    {
        parent::updateDataQuery($query);
        $query->limit = \PHP_INT_MAX;
    }

    #[\Override]
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $results->pageList = [];
            $results->addAttribute('search', false);
            $results->addAttribute('pagination', false);
        }
    }
}
