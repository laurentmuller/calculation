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

use App\Repository\CustomerRepository;
use App\Util\FileUtils;

/**
 * The customers table.
 *
 * @template-extends AbstractEntityTable<\App\Entity\Customer>
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
        return FileUtils::buildPath(__DIR__, 'Definition', 'customer.json');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return [CustomerRepository::NAME_COMPANY_FIELD => self::SORT_ASC];
    }
}
