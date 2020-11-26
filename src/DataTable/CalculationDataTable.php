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
use App\Entity\Calculation;
use App\Repository\CalculationRepository;
use App\Util\FormatUtils;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

/**
 * Calculation data table handler.
 *
 * @author Laurent Muller
 */
class CalculationDataTable extends AbstractEntityDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = Calculation::class;

    /**
     * Constructor.
     *
     * @param SessionInterface      $session     the session to save/retrieve user parameters
     * @param DataTablesInterface   $datatables  the datatables to handle request
     * @param CalculationRepository $repository  the repository to get entities
     * @param Environment           $environment the Twig environment to render actions cells
     */
    public function __construct(SessionInterface $session, DataTablesInterface $datatables, CalculationRepository $repository, Environment $environment)
    {
        parent::__construct($session, $datatables, $repository, $environment);
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
        return FormatUtils::formatAmount($value);
    }

    /**
     * Format the date.
     *
     * @param \DateTimeInterface $date the date to format
     *
     * @return string the formatted date
     */
    public function dateFormatter(\DateTimeInterface $date): string
    {
        return FormatUtils::formatDate($date);
    }

    /**
     * Format the identifier.
     *
     * @param int $id the identifier to format
     *
     * @return string the formatted identifier
     */
    public function idFormatter(int $id): string
    {
        return FormatUtils::formatId($id);
    }

    /**
     * Formats the given margin as percent.
     *
     * @param float $value the value to format
     *
     * @return string the formatted value
     */
    public function marginFormatter(float $value): string
    {
        return FormatUtils::formatPercent($value);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/calculation.json';

        return DataColumnFactory::fromJson($this, $path);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['id' => DataColumn::SORT_DESC];
    }
}
