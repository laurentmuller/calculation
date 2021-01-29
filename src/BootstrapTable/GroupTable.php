<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\BootstrapTable;

use App\Repository\GroupRepository;

/**
 * The goups table.
 *
 * @author Laurent Muller
 */
class GroupTable extends AbstractEntityTable
{
    /**
     * Constructor.
     */
    public function __construct(GroupRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return __DIR__ . '/Definition/group.json';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['code' => Column::SORT_ASC];
    }
}
