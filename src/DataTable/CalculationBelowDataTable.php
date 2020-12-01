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

use App\Repository\AbstractRepository;
use App\Repository\CalculationRepository;
use App\Service\ApplicationService;
use DataTables\DataTablesInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

/**
 * Data table handler for calculation with margin below the minimum.
 *
 * @author Laurent Muller
 */
class CalculationBelowDataTable extends CalculationDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = 'Calculation.below';

    /**
     * The application service.
     *
     * @var ApplicationService
     */
    private $application;

    /**
     * Constructor.
     *
     * @param SessionInterface      $session     the session to save/retrieve user parameters
     * @param DataTablesInterface   $datatables  the datatables to handle request
     * @param CalculationRepository $repository  the repository to get entities
     * @param Environment           $environment the Twig environment to render actions cells
     * @param ApplicationService    $application the application to get parameters
     */
    public function __construct(SessionInterface $session, DataTablesInterface $datatables, CalculationRepository $repository, Environment $environment, ApplicationService $application)
    {
        parent::__construct($session, $datatables, $repository, $environment);
        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    protected function createQueryBuilder($alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        $param = 'minMargin';
        $itemsField = "{$alias}.itemsTotal";
        $overallField = "{$alias}.overallTotal";
        $minMargin = $this->application->getMinMargin();

        return parent::createQueryBuilder($alias)
            ->andWhere("{$itemsField} != 0")
            ->andWhere("({$overallField} / {$itemsField}) < :{$param}")
            ->setParameter($param, $minMargin, Types::FLOAT);
    }
}
