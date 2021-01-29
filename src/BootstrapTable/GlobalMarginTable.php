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

use App\Repository\GlobalMarginRepository;

/**
 * The global margins table.
 *
 * @author Laurent Muller
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
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return __DIR__ . '/Definition/global_margin.json';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['minimum' => Column::SORT_ASC];
    }
}
