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

use App\Repository\CustomerRepository;

/**
 * The customers table.
 *
 * @author Laurent Muller
 */
class CustomerTable extends AbstractEntityTable
{
    /**
     * Constructor.
     */
    public function __construct(CustomerRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function getColumnDefinitions(): string
    {
        return __DIR__ . '/Definition/customer.json';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return [CustomerRepository::NAME_COMPANY_FIELD => Column::SORT_ASC];
    }
}
