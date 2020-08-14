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

namespace App\DataTable;

use App\DataTable\Model\AbstractEntityDataTable;
use App\DataTable\Model\DataColumn;
use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Service\ApplicationService;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

/**
 * Customer data table handler.
 *
 * @author Laurent Muller
 */
class CustomerDataTable extends AbstractEntityDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = Customer::class;

    /**
     * Constructor.
     *
     * @param ApplicationService  $application the application to get parameters
     * @param SessionInterface    $session     the session to save/retrieve user parameters
     * @param DataTablesInterface $datatables  the datatables to handle request
     * @param CustomerRepository  $repository  the repository to get entities
     * @param Environment         $environment the Twig environment to render actions cells
     */
    public function __construct(ApplicationService $application, SessionInterface $session, DataTablesInterface $datatables, CustomerRepository $repository, Environment $environment)
    {
        parent::__construct($application, $session, $datatables, $repository, $environment);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        return [
            DataColumn::hidden('id'),
            DataColumn::instance('nameAndCompany')
                ->setTitle('customer.fields.nameAndCompany')
                ->setMap(['company', 'firstName', 'lastName'])
                ->setDefault(true)
                ->setClassName('w-50 cell'),
            DataColumn::instance('address')
                ->setTitle('customer.fields.address')
                ->setClassName('w-auto cell'),
            DataColumn::instance('zipCity')
                ->setTitle('customer.fields.zipCity')
                ->setMap(['zipCode', 'city'])
                ->setClassName('w-25 cell'),
            DataColumn::actions([$this, 'renderActions']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['nameAndCompany' => DataColumn::SORT_ASC];
    }
}
