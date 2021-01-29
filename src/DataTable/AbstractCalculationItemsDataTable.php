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

namespace App\DataTable;

use App\DataTable\Model\AbstractDataTable;
use App\DataTable\Model\DataColumnFactory;
use App\Repository\CalculationRepository;
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
abstract class AbstractCalculationItemsDataTable extends AbstractDataTable
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
    public function formatActions(int $id): string
    {
        return $this->environment->render('macros/_datatables_actions.html.twig', ['id' => $id]);
    }

    /**
     * Formats the invalid calculation items.
     *
     * @param array $items the invalid calculation items
     *
     * @return string the formatted items
     */
    abstract public function formatItems(array $items): string;

    /**
     * Gets the number of empty items.
     */
    public function getItemCounts(): int
    {
        return $this->itemsCount;
    }

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
        $results->data = \array_map([$this, 'getCellValues'], $filtered);

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
}
