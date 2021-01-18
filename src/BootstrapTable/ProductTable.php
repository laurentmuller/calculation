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

/**
 * The products table.
 *
 * @author Laurent Muller
 */
class ProductTable extends AbstractBootstrapTable
{
    /**
     * (non-PHPdoc).
     *
     * @see \App\BootstrapTable\AbstractBootstrapTable::createColumns()
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/product.json';

        return $this->deserializeColumns($path);
    }
}
