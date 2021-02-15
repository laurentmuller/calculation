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
abstract class AbstractTable
{
    use MathTrait;

    /**
     * The allowed page sizes.
     */
    public const PAGE_LIST = [5, 10, 15, 20, 25, 50];

    /**
     * The default page size.
     */
    public const PAGE_SIZE = 20;

    /**
     * The card parameter name.
     */
    public const PARAM_CARD = 'card';

    /**
     * The columns parameter name.
     */
    public const PARAM_COLUMNS = 'columns';

    /**
     * The identifier parameter name.
     */
    public const PARAM_ID = 'id';

    /**
     * The limit parameter name.
     */
    public const PARAM_LIMIT = 'limit';

    /**
     * The offset parameter name.
     */
    public const PARAM_OFFSET = 'offset';

    /**
     * The order parameter name.
     */
    public const PARAM_ORDER = 'order';

    /**
     * The page parameter name.
     */
    public const PARAM_PAGE = 'page';

    /**
     * The page list parameter name.
     */
    public const PARAM_PAGE_LIST = 'pageList';

    /**
     * The rows parameter name.
     */
    public const PARAM_ROWS = 'rows';

    /**
     * The search parameter name.
     */
    public const PARAM_SEARCH = 'search';

    /**
     * The sort parameter name.
     */
    public const PARAM_SORT = 'sort';

    /**
     * The total parameter name.
     */
    public const PARAM_TOTAL = 'total';

    /**
     * The total not filtred parameter name.
     */
    public const PARAM_TOTAL_NOT_FILTERED = 'totalNotFiltered';

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
        return FormatUtils::formatDate($value);
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
     * Gets the entity class name or null if not applicable.
     */
    abstract public function getEntityClassName(): ?string;

    /**
     * Handles the given request and returns the result parameters.
     */
    abstract public function handleRequest(Request $request): array;

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
            $session->set($key, $value);

            return true;
        }

        return false;
    }

    /**
     * Cleans the parameters to return.
     */
    protected function cleanParameters(Request $request, array $parameters): array
    {
        if ($request->isXmlHttpRequest()) {
            return [
                self::PARAM_TOTAL_NOT_FILTERED => $parameters[self::PARAM_TOTAL_NOT_FILTERED] ?? 0,
                self::PARAM_TOTAL => $parameters[self::PARAM_TOTAL] ?? 0,
                self::PARAM_ROWS => $parameters[self::PARAM_ROWS] ?? [],
            ];
        }
        $parameters[self::PARAM_COLUMNS] = $this->getColumns();

        return $parameters;
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
     * Gets the JSON file containing the column definitions.
     */
    abstract protected function getColumnDefinitions(): string;

    /**
     * Gets the default column.
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
     * Gets the allowed page list.
     *
     * @param int $totalNotFiltered the number of not filtered entities
     *
     * @return int[] the allowed page list
     */
    protected function getPageList(int $totalNotFiltered): array
    {
        $sizes = self::PAGE_LIST;
        for ($i = 0, $count = \count($sizes); $i < $count; ++$i) {
            if ($sizes[$i] >= $totalNotFiltered) {
                return \array_slice($sizes, 0, $i + 1);
            }
        }

        return $sizes;
    }

    /**
     * Gets the display card parameter.
     */
    protected function getParamCard(Request $request): bool
    {
        $value = $this->getRequestValue($request, self::PARAM_CARD, false);

        return (bool) \filter_var($value, \FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Gets the request parameters.
     */
    protected function getParameters(Request $request): array
    {
        // offset and limit
        $offset = (int) $request->get(self::PARAM_OFFSET, 0);
        $limit = (int) $this->getRequestValue($request, self::PARAM_LIMIT, self::PAGE_SIZE);
        $page = 1 + (int) \floor($this->safeDivide($offset, $limit));

        // sort and order
        $sort = '';
        $order = Column::SORT_ASC;
        $defaultOrder = $this->getDefaultOrder();
        if (!empty($defaultOrder)) {
            $sort = \array_key_first($defaultOrder);
            $order = $defaultOrder[$sort];
        }

        $result = [
            self::PARAM_SEARCH => (string) $request->get(self::PARAM_SEARCH, ''),

            self::PARAM_ID => $this->getParamId($request),
            self::PARAM_CARD => $this->getParamCard($request),

            self::PARAM_SORT => (string) $this->getRequestValue($request, self::PARAM_SORT, $sort),
            self::PARAM_ORDER => (string) $this->getRequestValue($request, self::PARAM_ORDER, $order),

            self::PARAM_OFFSET => $offset,
            self::PARAM_LIMIT => $limit,
            self::PARAM_PAGE => $page,
        ];

        return $result;
    }

    /**
     * Gets the selected identifier parameter.
     */
    protected function getParamId(Request $request): int
    {
        return (int) $request->get(self::PARAM_ID, 0);
    }

    /**
     * Gets the request parameter value.
     *
     * @param Request $request the request to get value from
     * @param string  $name    the parameter name
     * @param mixed   $default the default value if not found
     *
     * @return mixed the parameter value
     */
    protected function getRequestValue(Request $request, string $name, $default = null)
    {
        $key = $this->getSessionKey($name);
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
     * @param array $entities the entities to map
     *
     * @return array the mapped entities
     */
    protected function mapEntities(array $entities): array
    {
        if (!empty($entities)) {
            $columns = $this->getColumns();
            $accessor = PropertyAccess::createPropertyAccessor();

            return \array_map(function ($entity) use ($columns, $accessor) {
                return $this->mapValues($entity, $columns, $accessor);
            }, $entities);
        }

        return [];
    }

    /**
     * Map the given object to an array where the keys are the column field.
     *
     * @param mixed            $objectOrArray the object to map
     * @param Column[]         $columns       the column definitions
     * @param PropertyAccessor $accessor      the property accessor to get the object values
     *
     * @return string[] the mapped object
     */
    protected function mapValues($objectOrArray, array $columns, PropertyAccessor $accessor): array
    {
        $callback = static function (array $result, Column $column) use ($objectOrArray, $accessor) {
            $result[$column->getField()] = $column->mapValue($objectOrArray, $accessor);

            return $result;
        };

        return \array_reduce($columns, $callback, []);
    }

    /**
     * Update the parameters before sending back.
     */
    protected function updateParameters(array $parameters): array
    {
        return $parameters;
    }
}
