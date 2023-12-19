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

use App\Entity\Customer;
use App\Interfaces\SortModeInterface;
use App\Repository\CustomerRepository;
use App\Utils\FileUtils;

/**
 * The customers table.
 *
 * @template-extends AbstractEntityTable<Customer, CustomerRepository>
 */
class CustomerTable extends AbstractEntityTable
{
    public function __construct(CustomerRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'customer.json');
    }

    protected function getDefaultOrder(): array
    {
        return [CustomerRepository::NAME_COMPANY_FIELD => SortModeInterface::SORT_ASC];
    }
}
