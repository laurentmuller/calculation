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

use App\DataTable\Model\AbstractDataTable;
use App\DataTable\Model\DataColumnFactory;
use App\Repository\CalculationRepository;
use App\Util\FormatUtils;
use DataTables\DataTableQuery;
use DataTables\DataTableResults;
use DataTables\DataTablesInterface;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

/**
 * Abstract data table handler for calculations with invalid items.
 *
 * @author Laurent Muller
 */
abstract class CalculationItemsDataTable extends AbstractDataTable
{
    /**
     * @var Environment
     */
    protected $environment;

    /**
     * The number of items.
     *
     * @var int
     */
    private $itemsCount = 0;

    /**
     * The repository to get entities.
     *
     * @var CalculationRepository
     */
    private $repository;

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
        parent::__construct($session, $datatables);
        $this->repository = $repository;
        $this->environment = $environment;
    }

    /**
     * Renders the actions column.
     *
     * @param int $id the entity identifier
     */
    public function actionsFormatter(int $id): string
    {
        return $this->environment->render('macros/_datatables_actions.html.twig', ['id' => $id]);
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
     * Gets the number of empty items.
     */
    public function getItemCounts(): int
    {
        return $this->itemsCount;
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
     * Formats the invalid calculation items.
     *
     * @param array $items the invalid calculation items
     *
     * @return string the formatted items
     */
    abstract public function itemsFormatter(array $items): string;

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/calculation_items.json';

        return  DataColumnFactory::fromJson($this, $path);
    }

    /**
     * {@inheritdoc}
     */
    protected function createDataTableResults(DataTableQuery $query): DataTableResults
    {
        // sort mode
        $orderColumn = 'id';
        $orderDirection = Criteria::DESC;
        if ($order = $this->getFirstRequestOrder($query)) {
            $orderColumn = $order['column']->getName();
            $orderDirection = $order['direction'];
        }

        // find all
        $items = $this->getItems($this->repository, $orderColumn, $orderDirection);

        // create results
        $results = new DataTableResults();
        $results->recordsFiltered = $results->recordsTotal = \count($items);
        $this->itemsCount = $this->getItemsCount($items);

        // filter
        $offset = $query->start;
        $limit = $query->length;
        if (-1 === $limit) {
            $filtered = \array_slice($items, $offset);
        } else {
            $filtered = \array_slice($items, $offset, $limit);
        }

        // transform
        $results->data = \array_map([$this, 'toArray'], $filtered);

        return $results;
    }

    /**
     * Gets the invalid items.
     *
     * @param CalculationRepository $repository     the calculation repository
     * @param string                $orderColumn    the order column
     * @param string                $orderDirection the order direction ('ASC' or 'DESC')
     */
    abstract protected function getItems(CalculationRepository $repository, string $orderColumn, string $orderDirection): array;

    /**
     * Compute the number of items.
     *
     * @param array $items the calculations
     *
     * @return int the number of items
     */
    abstract protected function getItemsCount(array $items): int;

    /**
     * Converts the given item to an array.
     *
     * @param array $item the item to convert
     */
    protected function toArray(array $item): array
    {
        return $this->getCellValues($item);
    }
}
