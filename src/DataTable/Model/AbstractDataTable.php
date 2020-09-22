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

namespace App\DataTable\Model;

use App\Service\ApplicationService;
use App\Traits\FormatterTrait;
use App\Traits\SessionTrait;
use App\Util\Utils;
use DataTables\AbstractDataTableHandler;
use DataTables\DataTableQuery;
use DataTables\DataTableResults;
use DataTables\DataTablesInterface;
use DataTables\Order;
use DataTables\Parameters;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Abstract data table handler.
 *
 * @author Laurent Muller
 */
abstract class AbstractDataTable extends AbstractDataTableHandler
{
    use FormatterTrait;
    use SessionTrait;

    /**
     * The default page length.
     */
    public const PAGE_LENGTH = 15;

    /**
     * The default page start.
     */
    public const PAGE_START = 0;

    /**
     * The show all records.
     */
    public const SHOW_ALL = -1;

    /**
     * The order column parameter name.
     */
    private const PARAM_ORDER_COLUMN = 'ordercolumn';

    /**
     * The order direction parameter name.
     */
    private const PARAM_ORDER_DIR = 'orderdir';

    /**
     * The page index parameter name.
     */
    private const PARAM_PAGE_INDEX = 'page';

    /**
     * The page length parameter name.
     */
    private const PARAM_PAGE_LENGTH = 'pagelength';

    /**
     * The columns search parameter name.
     */
    private const PARAM_SEARCH_COLUMNS = 'search';

    /**
     * The JSON (XMLHttpRequest) callback state.
     *
     * @var bool
     */
    protected $callback = false;

    /**
     * The columns.
     *
     * @var DataColumn[]
     */
    protected $columns;

    /**
     * The datatables to handle request.
     *
     * @var DataTablesInterface
     */
    protected $datatables;

    /**
     * The parameter prefix for session.
     */
    protected $sessionPrefix;

    /**
     * Constructor.
     *
     * @param ApplicationService  $application the application to get parameters
     * @param SessionInterface    $session     the session to save/retrieve user parameters
     * @param DataTablesInterface $datatables  the datatables to handle request
     */
    public function __construct(ApplicationService $application, SessionInterface $session, DataTablesInterface $datatables)
    {
        $this->application = $application;
        $this->session = $session;
        $this->datatables = $datatables;
    }

    /**
     * Creates a data table query for the given request.
     *
     * @param Request $request the request
     *
     * @return DataTableQuery the query
     */
    public function createDataTableQuery(Request $request): DataTableQuery
    {
        // parameters
        $params = $this->createParameters($request);

        return new DataTableQuery($params);
    }

    /**
     * Gets the cell values for the given data (row).
     *
     * The default implementation call the <code>getCellValue($data)</code> function and the <code>formatValue($value, $data)</code> function for each column.
     *
     * @param array|object $data the object or array to traverse for getting values
     *
     * @return string[] the cell values
     *
     * @see DataColumn::getCellValue()
     * @see DataColumn::formatValue()
     */
    public function getCellValues($data): array
    {
        $result = [];
        $columns = $this->getColumns();

        foreach ($columns as $column) {
            $value = $column->getCellValue($data);
            $result[] = $column->formatValue($value, $data);
        }

        return $result;
    }

    /**
     * Gets the data column at the given index.
     *
     * @param int $index the index of the column
     *
     * @return DataColumn|null the data column, if index is valid; null otherwise
     */
    public function getColumn(int $index): ?DataColumn
    {
        $columns = $this->getColumns();
        if ($index >= 0 && $index < \count($columns)) {
            return $columns[$index];
        }

        return null;
    }

    /**
     * Gets the data columns.
     *
     * @return DataColumn[]
     */
    public function getColumns(): array
    {
        if (empty($this->columns)) {
            $this->columns = $this->createColumns();
        }

        return $this->columns;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(DataTableQuery $query): DataTableResults
    {
        // create results
        $results = $this->createDataTableResults($query);

        // save parameters
        $this->setSessionValue(self::PARAM_PAGE_LENGTH, $query->length);
        if (empty($query->order)) {
            $this->removeSessionValue(self::PARAM_ORDER_COLUMN);
            $this->removeSessionValue(self::PARAM_ORDER_DIR);
        } else {
            $order = $query->order[0];
            $this->setSessionValue(self::PARAM_ORDER_COLUMN, $order->column);
            $this->setSessionValue(self::PARAM_ORDER_DIR, $order->dir);
        }

        return $results;
    }

    /**
     * Handle the HTTP request.
     *
     * @param Request $request the request to handle
     *
     * @return DataTableResults the datatable results
     */
    public function handleRequest(Request $request): DataTableResults
    {
        // callback?
        if ($this->callback = $request->isXmlHttpRequest()) {
            $result = $this->datatables->handle($request, static::ID);
        } else {
            $query = $this->createDataTableQuery($request);
            $result = $this->handle($query);
        }

        return $result;
    }

    /**
     * Returns if the last handled request is a JSON (XMLHttpRequest) callback.
     *
     * @return bool true if JSON callback
     */
    public function isCallback(): bool
    {
        return $this->callback;
    }

    /**
     * Creates the datatable columns.
     *
     * @return DataColumn[] the columns
     */
    abstract protected function createColumns(): array;

    /**
     * Creates the data table results.
     *
     * @param DataTableQuery $query the data table query
     */
    abstract protected function createDataTableResults(DataTableQuery $query): DataTableResults;

    /**
     * Creates a data table parameters.
     *
     * @param Request $request the request
     *
     * @return Parameters the parameters
     */
    protected function createParameters(Request $request): Parameters
    {
        // get request values
        $query = $request->get('query');
        $page = (int) $this->getRequestValue($request, self::PARAM_PAGE_INDEX, self::PAGE_START);
        $pagelength = (int) $this->getRequestValue($request, self::PARAM_PAGE_LENGTH, self::PAGE_LENGTH);
        $ordercolumn = $this->getRequestValue($request, self::PARAM_ORDER_COLUMN);
        $orderdir = $this->getRequestValue($request, self::PARAM_ORDER_DIR);
        $searchColumns = $this->getRequestValue($request, self::PARAM_SEARCH_COLUMNS, []);

        // convert search columns
        $searchColumns = \array_reduce($searchColumns, function (array $carry, array $entry) {
            $carry[$entry['index']] = $entry['value'];

            return $carry;
        }, []);

        // parameters
        $params = new Parameters();
        $params->search = DataColumn::createSearch($query);
        $params->start = \max($page * $pagelength, 0);
        $params->length = $pagelength;

        // columns
        $columns = $this->getColumns();
        $params->columns = Utils::arrayMapKey(function (int $key, DataColumn $column) use ($searchColumns) {
            $search = $searchColumns[$key] ?? null;
            $key = $this->getColumnKey($key, $column);

            return $column->toArray($key, $search);
        }, $columns);

        // order
        if (null !== $ordercolumn && null !== $orderdir) {
            $params->order = $this->createOrderParameter($ordercolumn, $orderdir);
        } else {
            $params->order = $this->findColumnOrder($columns);
        }

        return $params;
    }

    /**
     * Creates the session prefix.
     */
    protected function createSessionPrefix(): string
    {
        return Utils::getShortName(static::class);
    }

    /**
     * Finds the column order.
     *
     * @param DataColumn[] $columns the columns to search in
     *
     * @return array the column order
     */
    protected function findColumnOrder(array $columns): array
    {
        $len = \count($columns);

        // find default ordered column
        for ($i = 0; $i < $len; ++$i) {
            if ($columns[$i]->isDefault() && $columns[$i]->isOrderable()) {
                return $this->createOrderParameter($i, $columns[$i]->getDirection());
            }
        }

        // find first visible and orderable column
        for ($i = 0; $i < $len; ++$i) {
            if ($columns[$i]->isVisible() && $columns[$i]->isOrderable()) {
                return $this->createOrderParameter($i, $columns[$i]->getDirection());
            }
        }

        // none
        return [];
    }

    /**
     * Gets the key used to get column value.
     *
     * By default, return the column index. Subclass can override to returns an other key.
     *
     * @param int        $key    the column index
     * @param DataColumn $column the column
     *
     * @return mixed the key
     */
    protected function getColumnKey(int $key, DataColumn $column)
    {
        return $key;
    }

    /**
     * Gets the first column order.
     *
     * @param DataTableQuery $request the data table query to get order for
     *
     * @return array|null an array with the column index ('index'), the data column ('column') and the sort direction ('direction') or null if no order is set
     */
    protected function getFirstRequestOrder(DataTableQuery $request): ?array
    {
        if (!empty($request->order)) {
            /** @var Order $order */
            $order = $request->order[0];
            $index = $order->column;
            $dir = $order->dir;
            $column = $this->getColumn($index);

            return [
                'index' => $index,
                'column' => $column,
                'direction' => $dir,
            ];
        }

        return null;
    }

    /**
     * Gets a request parameter. This function try first to get value from request, then from the session
     * and if not found return the default.
     *
     * @param Request $request the request
     * @param string  $key     the parameter key to search for
     * @param mixed   $default the default value if not found
     *
     * @return mixed the value, if found; the default value otherwise
     */
    protected function getRequestValue(Request $request, string $key, $default = null)
    {
        // find within the session
        $sessionValue = $this->getSessionValue($key, $default);

        // find within the request
        return $request->get($key, $sessionValue);
    }

    /**
     * Gets the local key used to save or retrieve request or session values.
     *
     * @param string $key the key name
     *
     * @return string the local key
     */
    protected function getSessionKey(string $key): string
    {
        $prefix = $this->getSessionPrefix();

        return "{$prefix}.{$key}";
    }

    /**
     * Gets the prefix used to save or retrieve session values.
     *
     * @return string the prefix
     */
    protected function getSessionPrefix(): string
    {
        if (!$this->sessionPrefix) {
            $this->sessionPrefix = $this->createSessionPrefix();
        }

        return $this->sessionPrefix;
    }

    /**
     * Creates an order parameters.
     *
     * @param mixed  $column the column key
     * @param string $dir    the sort direction ('asc' or 'desc')
     *
     * @return array the order parameters
     */
    private function createOrderParameter($column, string $dir): array
    {
        return [['column' => $column, 'dir' => $dir]];
    }
}