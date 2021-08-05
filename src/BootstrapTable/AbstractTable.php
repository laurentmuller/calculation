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

namespace App\BootstrapTable;

use App\Entity\AbstractEntity;
use App\Interfaces\SortModeInterface;
use App\Interfaces\TableInterface;
use App\Traits\MathTrait;
use App\Util\FormatUtils;
use App\Util\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * The abstract table.
 *
 * @author Laurent Muller
 */
abstract class AbstractTable implements SortModeInterface
{
    use MathTrait;

    /**
     * The column definitions.
     *
     * @var Column[]
     */
    protected ?array $columns = null;

    /**
     * The session prefix.
     */
    private ?string $prefix = null;

    public function formatAmount(float $value): string
    {
        return FormatUtils::formatAmount($value);
    }

    public function formatCountable(\Countable $value): string
    {
        return $this->formatInt($value->count());
    }

    public function formatDate(\DateTimeInterface $value): string
    {
        return (string) FormatUtils::formatDate($value);
    }

    public function formatId(int $value): string
    {
        return FormatUtils::formatId($value);
    }

    public function formatInt(int $value): string
    {
        return FormatUtils::formatInt($value);
    }

    public function formatPercent(float $value): string
    {
        return FormatUtils::formatPercent($value);
    }

    /**
     * Gets the column definitions.
     *
     * @return Column[]
     */
    public function getColumns(): array
    {
        if (null === $this->columns) {
            $this->columns = $this->createColumns();
        }

        return $this->columns;
    }

    /**
     * Gets the data query from the given request.
     */
    public function getDataQuery(Request $request): DataQuery
    {
        $query = new DataQuery();

        // global parameters
        $query->id = $this->getParamId($request);
        $query->callback = $request->isXmlHttpRequest();
        $query->search = (string) $request->get(TableInterface::PARAM_SEARCH, '');
        $query->view = (string) $this->getRequestValue($request, TableInterface::PARAM_VIEW, TableInterface::VIEW_TABLE, false);

        // limit, offset and page
        switch ($query->view) {
            case TableInterface::VIEW_CARD:
                $query->limit = (int) $this->getRequestValue($request, TableInterface::PARAM_LIMIT, TableInterface::PAGE_SIZE_CARD);
                break;
            case TableInterface::VIEW_CUSTOM:
                $query->limit = (int) $this->getRequestValue($request, TableInterface::PARAM_LIMIT, TableInterface::PAGE_SIZE_CUSTOM);
                break;
            default: // TableInterface::VIEW_TABLE
                $query->limit = (int) $this->getRequestValue($request, TableInterface::PARAM_LIMIT, TableInterface::PAGE_SIZE);
                break;
        }
        $query->offset = (int) $request->get(TableInterface::PARAM_OFFSET, 0);
        $query->page = 1 + (int) \floor($this->safeDivide($query->offset, $query->limit));

        // sort and order
        if ($column = $this->getDefaultColumn()) {
            $query->sort = $column->getField();
            $query->order = $column->getOrder();
        }
        $query->sort = (string) $this->getRequestValue($request, TableInterface::PARAM_SORT, $query->sort);
        $query->order = (string) $this->getRequestValue($request, TableInterface::PARAM_ORDER, $query->order);

        return $query;
    }

    /**
     * Gets the empty message to show when no records are available.
     */
    public function getEmptyMessage(): string
    {
        return 'list.empty_list';
    }

    /**
     * Gets the entity class name or null if not applicable.
     */
    public function getEntityClassName(): ?string
    {
        return null;
    }

    /**
     * Returns a value indicating whether this table should be shown, even if no records are available.
     */
    public function isEmptyAllowed(): bool
    {
        return true;
    }

    /**
     * Process the given query and returns the results.
     *
     * @param DataQuery $query the query to handle
     *
     * @return DataResults the results
     */
    public function processQuery(DataQuery $query): DataResults
    {
        $results = $this->handleQuery($query);
        $this->updateResults($query, $results);

        return $results;
    }

    /**
     * Save the request parameter value to the session.
     *
     * @param Request $request the request to get value from
     * @param string  $name    the parameter name
     * @param mixed   $default the default value if not found
     *
     * @return bool true if the parameter value is saved to the session; false otherwise
     */
    public function saveRequestValue(Request $request, string $name, $default = null): bool
    {
        if ($request->hasSession()) {
            $session = $request->getSession();
            $key = $this->getSessionKey($name);
            $default = $session->get($key, $default);
            $value = $request->get($name, $default);
            if (null === $value) {
                $session->remove($key);
            } else {
                $session->set($key, $value);
            }

            return true;
        }

        return false;
    }

    /**
     * Create the columns.
     *
     * @return Column[] the columns
     */
    protected function createColumns(): array
    {
        $path = $this->getColumnDefinitions();

        return Column::fromJson($this, $path);
    }

    /**
     * Gets the allowed page list.
     *
     * @param int $totalNotFiltered the number of not filtered entities
     *
     * @return int[] the allowed page list
     */
    protected function getAllowedPageList(int $totalNotFiltered): array
    {
        $sizes = TableInterface::PAGE_LIST;
        if (\end($sizes) <= $totalNotFiltered) {
            return $sizes;
        }

        for ($i = 0, $count = \count($sizes); $i < $count; ++$i) {
            if ($sizes[$i] >= $totalNotFiltered) {
                return \array_slice($sizes, 0, $i + 1);
            }
        }

        // must never been here!
        return $sizes;
    }

    /**
     * Gets the JSON file containing the column definitions.
     */
    abstract protected function getColumnDefinitions(): string;

    /**
     * Gets the JavaScript function used to format the custom view.
     */
    protected function getCustomViewFormatter(): string
    {
        return 'customViewFormatter';
    }

    /**
     * Gets the default sorting column.
     */
    protected function getDefaultColumn(): ?Column
    {
        $columns = $this->getColumns();
        foreach ($columns as $column) {
            if ($column->isDefault()) {
                return $column;
            }
        }
        foreach ($columns as $column) {
            if ($column->isVisible()) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Gets the default order to apply.
     *
     * @return array an array where each key is the field name and the value is the order direction ('asc' or 'desc')
     */
    protected function getDefaultOrder(): array
    {
        return [];
    }

    /**
     * Gets the request parameter value.
     *
     * @param Request $request       the request to get value from
     * @param string  $name          the parameter name
     * @param mixed   $default       the default value if not found
     * @param bool    $useSessionKey true to use session key; false to use the parameter name
     *
     * @return mixed the parameter value
     */
    protected function getRequestValue(Request $request, string $name, $default = null, bool $useSessionKey = true)
    {
        $key = $useSessionKey ? $this->getSessionKey($name) : $name;
        $session = $request->hasSession() ? $request->getSession() : null;

        if ($session) {
            $default = $session->get($key, $default);
        }

        $value = $request->get($name, $default);

        if ($session) {
            $session->set($key, $value);
        }

        return $value;
    }

    /**
     * Gets the session key for the given name.
     *
     * @param string $name the parameter name
     */
    protected function getSessionKey(string $name): string
    {
        if (null === $this->prefix) {
            $this->prefix = Utils::getShortName($this);
        }

        return "{$this->prefix}.$name";
    }

    /**
     * Handle the query parameters.
     *
     * @param DataQuery $query the query parameters
     *
     * @return DataResults the data results
     */
    protected function handleQuery(DataQuery $query): DataResults
    {
        return new DataResults();
    }

    /**
     * Implode the given page list.
     *
     * @param int[] $pageList the page list
     */
    protected function implodePageList(array $pageList): string
    {
        return '[' . \implode(',', $pageList) . ']';
    }

    /**
     * Returns a value indicating if the custom view is allowed (true by default).
     */
    protected function isCustomViewAllowed(): bool
    {
        return true;
    }

    /**
     * Maps the given entities.
     *
     * @param array $entities the entities to map
     *
     * @return array the mapped entities
     */
    protected function mapEntities(array $entities): array
    {
        if (!empty($entities)) {
            $columns = $this->getColumns();
            $accessor = PropertyAccess::createPropertyAccessor();

            // @phpstan-ignore-next-line
            return \array_map(function ($entity) use ($columns, $accessor): array {
                return $this->mapValues($entity, $columns, $accessor);
            }, $entities);
        }

        return [];
    }

    /**
     * Map the given object to an array where the keys are the column field.
     *
     * @param array|AbstractEntity $objectOrArray the object to map
     * @param Column[]             $columns       the column definitions
     * @param PropertyAccessor     $accessor      the property accessor to get the object values
     *
     * @return string[] the mapped object
     */
    protected function mapValues($objectOrArray, array $columns, PropertyAccessor $accessor): array
    {
        $callback = static function (array $result, Column $column) use ($objectOrArray, $accessor) {
            $result[$column->getAlias()] = $column->mapValue($objectOrArray, $accessor);

            return $result;
        };

        return \array_reduce($columns, $callback, []);
    }

    /**
     * Update the results before sending back.
     *
     * @param DataQuery   $query   the data query
     * @param DataResults $results the results to update
     */
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        // page list and limit
        $results->pageList = $this->getAllowedPageList($results->totalNotFiltered);
        $limit = (int) \min($query->limit, \max($results->pageList));

        // parameters
        $results->params = \array_merge([
            TableInterface::PARAM_ID => $query->id,
            TableInterface::PARAM_SEARCH => $query->search,
            TableInterface::PARAM_SORT => $query->sort,
            TableInterface::PARAM_ORDER => $query->order,
            TableInterface::PARAM_OFFSET => $query->offset,
            TableInterface::PARAM_VIEW => $query->view,
            TableInterface::PARAM_LIMIT => $limit,
        ], $results->params);

        // callback?
        if ($query->callback) {
            return;
        }

        // columns
        $results->columns = $this->getColumns();

        // attributes
        $results->attributes = \array_merge([
            'total-not-filtered' => $results->totalNotFiltered,
            'total-rows' => $results->filtered,

            'search' => \json_encode(true),
            'search-text' => $query->search,

            'page-list' => $this->implodePageList($results->pageList),
            'page-number' => $query->page,
            'page-size' => $limit,

            'card-view' => \json_encode($query->isViewCard()),

            'sort-name' => $query->sort,
            'sort-order' => $query->order,
        ], $results->attributes);

        // custom view?
        if ($this->isCustomViewAllowed()) {
            $results->attributes = \array_merge([
                'show-custom-view' => \json_encode($query->isViewCustom()),
                'custom-view' => $this->getCustomViewFormatter(),
            ], $results->attributes);
        }
    }

    /**
     * Gets the selected identifier parameter.
     */
    private function getParamId(Request $request): int
    {
        return (int) $request->get(TableInterface::PARAM_ID, 0);
    }
}
