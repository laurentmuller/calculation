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

namespace App\Tests\Fixture;

use App\Table\AbstractTable;
use App\Table\Column;
use App\Table\DataQuery;

class FixtureTable extends AbstractTable
{
    #[\Override]
    public function getAllowedPageList(int $totalNotFiltered): array
    {
        return parent::getAllowedPageList($totalNotFiltered);
    }

    #[\Override]
    public function updateDataQuery(DataQuery $query): void
    {
        parent::updateDataQuery($query);
    }

    #[\Override]
    protected function getColumnDefinitions(): string
    {
        return '';
    }

    #[\Override]
    protected function getDefaultColumn(): ?Column
    {
        return null;
    }
}
