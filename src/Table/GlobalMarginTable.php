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
use App\Utils\FileUtils;
use Symfony\Component\HttpFoundation\Request;

/**
 * The global margins table.
 *
 * @template-extends AbstractEntityTable<GlobalMargin, GlobalMarginRepository>
 */
class GlobalMarginTable extends AbstractEntityTable
{
    public function __construct(GlobalMarginRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getDataQuery(Request $request): DataQuery
    {
        $query = parent::getDataQuery($request);
        $query->limit = \PHP_INT_MAX;

        return $query;
    }

    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'global_margin.json');
    }

    protected function getDefaultOrder(): array
    {
        return ['minimum' => self::SORT_ASC];
    }

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
