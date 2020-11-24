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
use App\DataTable\Model\DataColumnFactory;
use App\Entity\GlobalMargin;
use App\Repository\GlobalMarginRepository;
use App\Service\ApplicationService;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

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
     * @param Environment            $environment the Twig environment to render actions cells
     */
    public function __construct(ApplicationService $application, SessionInterface $session, DataTablesInterface $datatables, GlobalMarginRepository $repository, Environment $environment)
    {
        parent::__construct($application, $session, $datatables, $repository, $environment);
    }

    /**
     * Format the amount.
     *
     * @param float $value the amount to format
     *
     * @return string the formatted amount
     */
    public function amountFormatter(float $value): string
    {
        return $this->localeAmount($value);
    }

    /**
     * Formats the given value as percent.
     *
     * @param float $number the value to format
     *
     * @return string the formatted value
     */
    public function percentFormatter(float $number): string
    {
        return $this->localePercent($number);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/global_margin.json';

        return DataColumnFactory::fromJson($this, $path);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['minimum' => DataColumn::SORT_ASC];
    }
}
