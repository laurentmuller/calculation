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
use Symfony\Component\Serializer\SerializerInterface;

/**
 * The customers table.
 *
 * @author Laurent Muller
 */
class CustomerTable extends AbstractBootstrapEntityTable
{
    /**
     * Constructor.
     */
    public function __construct(SerializerInterface $serializer, CustomerRepository $repository)
    {
        parent::__construct($serializer, $repository);
    }

    /**
     * (non-PHPdoc).
     *
     * @see \App\BootstrapTable\AbstractBootstrapTable::createColumns()
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/customer.json';

        return $this->deserializeColumns($path);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return [CustomerRepository::NAME_COMPANY_FIELD => BootstrapColumn::SORT_ASC];
    }
}
