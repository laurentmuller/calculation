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

use App\Repository\GlobalMarginRepository;
use App\Util\FileUtils;
use Symfony\Component\HttpFoundation\Request;

/**
 * The global margins table.
 *
 * @template-extends AbstractEntityTable<\App\Entity\GlobalMargin>
 */
class GlobalMarginTable extends AbstractEntityTable
{
    /**
     * Constructor.
     */
    public function __construct(GlobalMarginRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * {@inheritDoc}
     */
    public function getDataQuery(Request $request): DataQuery
    {
        // display always all records
        $query = parent::getDataQuery($request);
        $query->limit = \PHP_INT_MAX;

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'global_margin.json');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['minimum' => self::SORT_ASC];
    }

    /**
     * {@inheritDoc}
     */
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        // hide pages list
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $results->pageList = [];
            $results->addAttribute('search', \json_encode(false));
        }
    }
}
