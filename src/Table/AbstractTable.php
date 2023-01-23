<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Table;

use App\Entity\AbstractEntity;
use App\Enums\TableView;
use App\Interfaces\SortModeInterface;
use App\Interfaces\TableInterface;
use App\Traits\MathTrait;
use App\Traits\ParameterTrait;
use App\Util\FormatUtils;
use App\Util\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use function Symfony\Component\String\u;

/**
 * The abstract table.
 */
abstract class AbstractTable implements SortModeInterface
{
    use MathTrait;
    use ParameterTrait;

    /**
     * The column definitions.
     *
     * @var array<Column>
     */
    protected ?array $columns = null;

    /**
     * The cookie and session prefix.
     */
    private ?string $prefix = null;

    /**
     * Gets a translatable message if empty is not allowed and this contains no data.
     */
    public function checkEmpty(): ?string
    {
        if (!$this->isEmptyAllowed() && $this instanceof \Countable && 0 === \count($this)) {
            return $this->getEmptyMessage();
        }

        return null;
    }

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
     * @return array<Column>
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
        $query->callback = $request->isXmlHttpRequest();
        $query->id = $this->getParamInt($request, TableInterface::PARAM_ID);
        $query->search = (string) $this->getParamString($request, TableInterface::PARAM_SEARCH);

        // find view
        $view = (string) $this->getParamString($request, TableInterface::PARAM_VIEW, '', TableView::TABLE);
        $tableView = TableView::tryFrom($view) ?? TableView::TABLE;
        $query->view = $tableView;

        // find limit
        $limit = $this->getParamInt($request, TableInterface::PARAM_LIMIT, $this->getPrefix(), $tableView->getPageSize());
        $query->limit = $limit;

        // offset and page
        $query->offset = $this->getParamInt($request, TableInterface::PARAM_OFFSET);
        $query->page = 1 + (int) \floor($this->safeDivide($query->offset, $query->limit));

        // sort and order
        if (null !== ($column = $this->getDefaultColumn())) {
            $query->sort = $column->getField();
            $query->order = $column->getOrder();
        }
        $query->sort = (string) $this->getParamString($request, TableInterface::PARAM_SORT, '', $query->sort);
        $query->order = (string) $this->getParamString($request, TableInterface::PARAM_ORDER, '', $query->order);

        return $query;
    }

    /**
     * Gets the translatable message to show when no data is available.
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
     * Gets cookie and session prefix.
     *
     * @throws \ReflectionException
     */
    public function getPrefix(): string
    {
        if (null === $this->prefix) {
            $this->prefix = u(Utils::getShortName($this))->snake()->toString();
        }

        return $this->prefix;
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
     */
    public function processQuery(DataQuery $query): DataResults
    {
        $results = $this->handleQuery($query);
        $this->updateResults($query, $results);

        return $results;
    }

    /**
     * Returns a value indicating if the column action is added at the end of the columns.
     *
     * @see Column::createColumnAction()
     */
    protected function addColumnAction(): bool
    {
        return true;
    }

    /**
     * Create the columns.
     *
     * @return Column[] the columns
     */
    protected function createColumns(): array
    {
        $path = $this->getColumnDefinitions();
        $columns = Column::fromJson($this, $path);
        if ($this->addColumnAction()) {
            $columns[] = Column::createColumnAction();
        }

        return $columns;
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

        foreach ($sizes as $index => $size) {
            if ($size >= $totalNotFiltered) {
                return \array_slice($sizes, 0, $index + 1);
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
     * Gets the session key for the given name.
     *
     * @param string $name the parameter name
     *
     * @throws \ReflectionException
     */
    protected function getSessionKey(string $name): string
    {
        return $this->getPrefix() . '.' . $name;
    }

    /**
     * Handle the query parameters.
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
     * Maps the given entities.
     *
     * @param array<AbstractEntity|array> $entities the entities to map
     *
     * @return array<array<string, string>> the mapped entities
     */
    protected function mapEntities(array $entities): array
    {
        if (!empty($entities)) {
            $columns = $this->getColumns();
            $accessor = PropertyAccess::createPropertyAccessor();

            return \array_map(fn (AbstractEntity|array $entity): array => $this->mapValues($entity, $columns, $accessor), $entities);
        }

        return [];
    }

    /**
     * Map the given entity or array to an array where the keys are the column field.
     *
     * @param AbstractEntity|array $objectOrArray the entity or array to map
     * @param array<Column>        $columns       the column definitions
     * @param PropertyAccessor     $accessor      the property accessor to get the values
     *
     * @return array<string, string> the mapped object
     */
    protected function mapValues(AbstractEntity|array $objectOrArray, array $columns, PropertyAccessor $accessor): array
    {
        $callback = static function (array $result, Column $column) use ($objectOrArray, $accessor): array {
            $result[$column->getAlias()] = $column->mapValue($objectOrArray, $accessor);

            return $result;
        };

        /** @var array<string, string> $mappings */
        $mappings = \array_reduce($columns, $callback, []);

        return $mappings;
    }

    /**
     * Update the results before sending back.
     */
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        // page list and limit
        $results->pageList = $this->getAllowedPageList($results->totalNotFiltered);
        $limit = [] !== $results->pageList ? \min($query->limit, \max($results->pageList)) : $query->limit;

        // parameters
        $results->params = \array_merge([
            TableInterface::PARAM_ID => $query->id,
            TableInterface::PARAM_SEARCH => $query->search,
            TableInterface::PARAM_SORT => $query->sort,
            TableInterface::PARAM_ORDER => $query->order,
            TableInterface::PARAM_OFFSET => $query->offset,
            TableInterface::PARAM_VIEW => $query->view->value,
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

            'search' => true,
            'search-text' => $query->search,

            'page-size' => $limit,
            'page-number' => $query->page,
            'page-list' => $this->implodePageList($results->pageList),

            'sort-name' => $query->sort,
            'sort-order' => $query->order,

            'custom-view-default-view' => $query->isViewCustom(),
        ], $results->attributes);
    }
}
