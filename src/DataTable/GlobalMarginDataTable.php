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
use App\Entity\GlobalMargin;
use App\Repository\GlobalMarginRepository;
use App\Service\ApplicationService;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * GlobalMargin data table handler.
 *
 * @author Laurent Muller
 */
class GlobalMarginDataTable extends AbstractEntityDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = GlobalMargin::class;

    /**
     * Constructor.
     *
     * @param ApplicationService     $application the application to get parameters
     * @param SessionInterface       $session     the session to save/retrieve user parameters
     * @param DataTablesInterface    $datatables  the datatables to handle request
     * @param GlobalMarginRepository $repository  the repository to get entities
     */
    public function __construct(ApplicationService $application, SessionInterface $session, DataTablesInterface $datatables, GlobalMarginRepository $repository)
    {
        parent::__construct($application, $session, $datatables, $repository);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        // callbacks
        $percentFormatter = function (float $number): string {
            return $this->localePercent($number);
        };

        return [
            DataColumn::hidden('id'),
            DataColumn::currency('minimum')
                ->setTitle('categorymargin.fields.minimum')
                ->setSearchable(false)
                ->setDefault(true)
                ->setFormatter([$this, 'localeAmount']),
            DataColumn::currency('maximum')
                ->setTitle('categorymargin.fields.maximum')
                ->setSearchable(false)
                ->setFormatter([$this, 'localeAmount']),
            DataColumn::percent('margin')
                ->setTitle('categorymargin.fields.margin')
                ->setSearchable(false)
                ->setFormatter($percentFormatter),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['minimum' => DataColumn::SORT_ASC];
    }
}
