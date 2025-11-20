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

use App\Table\AbstractEntityTable;
use App\Table\Column;
use App\Table\DataQuery;
use App\Table\DataResults;

/**
 * @phpstan-ignore missingType.generics
 */
class FixtureEntityTable extends AbstractEntityTable
{
    #[\Override]
    public function getColumns(): array
    {
        return [];
    }

    #[\Override]
    public function handleQuery(DataQuery $query): DataResults
    {
        return parent::handleQuery($query);
    }

    #[\Override]
    protected function getColumnDefinitions(): string
    {
        return '';
    }

    #[\Override]
    protected function getDefaultColumn(): Column
    {
        return new Column();
    }
}
