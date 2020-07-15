<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\DataTables;

use App\DataTables\Columns\DataColumn;
use App\DataTables\Tables\AbstractEntityDataTable;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\ApplicationService;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Product data table handler.
 *
 * @author Laurent Muller
 */
class ProductDataTable extends AbstractEntityDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = Product::class;

    /**
     * Constructor.
     *
     * @param ApplicationService  $application the application to get parameters
     * @param SessionInterface    $session     the session to save/retrieve user parameters
     * @param DataTablesInterface $datatables  the datatables to handle request
     * @param ProductRepository   $repository  the repository to get entities
     */
    public function __construct(ApplicationService $application, SessionInterface $session, DataTablesInterface $datatables, ProductRepository $repository)
    {
        parent::__construct($application, $session, $datatables, $repository);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        return  [
            DataColumn::hidden('id'),
            DataColumn::instance('description')
                ->setTitle('product.fields.description')
                ->setClassName('w-50 cell')
                ->setDefault(true),
            DataColumn::instance('category.code')
                ->setTitle('product.fields.category')
                ->setClassName('cell'),
            DataColumn::instance('supplier')
                ->setTitle('product.fields.supplier')
                ->setClassName('cell'),
            DataColumn::unit('unit')
                ->setTitle('product.fields.unit'),
            DataColumn::currency('price')
                ->setTitle('product.fields.price')
                ->setFormatter([$this, 'localeAmount']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['description' => DataColumn::SORT_ASC];
    }
}
