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
use App\DataTable\Model\DataColumn;
use App\DataTable\Model\DataColumnFactory;
use App\Security\EntityVoter;
use App\Service\SearchService;
use App\Traits\CheckerTrait;
use App\Traits\TranslatorTrait;
use App\Util\Utils;
use DataTables\DataTableQuery;
use DataTables\DataTableResults;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Data table for search data in all entities.
 *
 * @author Laurent Muller
 */
class SearchDataTable extends AbstractDataTable
{
    use CheckerTrait;
    use TranslatorTrait;

    /**
     * The datatable identifier.
     */
    public const ID = self::class;

    /**
     * The content column name.
     */
    private const COLUMN_CONTENT = SearchService::COLUMN_CONTENT;

    /**
     * The delete granted column name.
     */
    private const COLUMN_DELETE = 'delete_granted';

    /**
     * The edit granted column name.
     */
    private const COLUMN_EDIT = 'edit_granted';

    /**
     * The entity column name.
     */
    private const COLUMN_ENTITY = 'entityName';

    /**
     * The entity column name.
     */
    private const COLUMN_FIELD = 'fieldName';

    /**
     * The show granted column name.
     */
    private const COLUMN_SHOW = 'show_granted';

    /**
     * The service to search entities.
     */
    private SearchService $service;

    /**
     * Constructor.
     */
    public function __construct(RequestStack $requestStack, DataTablesInterface $datatables, SearchService $service, AuthorizationCheckerInterface $checker, TranslatorInterface $translator)
    {
        parent::__construct($requestStack, $datatables);
        $this->service = $service;
        $this->checker = $checker;
        $this->setTranslator($translator);
    }

    /**
     * Gets the search service.
     */
    public function getService(): SearchService
    {
        return $this->service;
    }

    /**
     * Returns if the given action is granted for one or more entities.
     *
     * @param string $action the action to be tested
     *
     * @return bool true if granted
     */
    public function isActionGranted(string $action): bool
    {
        if (null !== $this->checker) {
            $entities = \array_keys(EntityVoter::ENTITY_OFFSETS);
            foreach ($entities as $entity) {
                if ($this->checker->isGranted($action, $entity)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/search.json';

        return DataColumnFactory::fromJson($this, $path);
    }

    /**
     * {@inheritdoc}
     */
    protected function createDataTableResults(DataTableQuery $query): DataTableResults
    {
        $results = new DataTableResults();

        // search?
        $search = $query->search->value;
        if ($search && \strlen($search) > 1) {
            $column = $query->columns[1];
            $entity = $column->search->value;

            // search
            $items = $this->service->search($search, $entity, SearchService::NO_LIMIT);

            // found?
            if (!empty($items)) {
                $limit = $query->length;
                $offset = $query->start;
                /** @psalm-param array<string, string> $orders */
                $orders = $this->getQueryOrders($query);

                $count = \count($items);
                $results->recordsTotal = $count;
                $results->recordsFiltered = $count;

                // process and sort
                $this->processItems($items);
                $this->sortItems($items, $orders);

                $results->data = \array_slice($items, $offset, $limit);
            }
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnKey(int $key, DataColumn $column)
    {
        return $column->getName();
    }

    /**
     * Gets the columns order for the given query.
     *
     * @param DataTableQuery $query the query to get order
     *
     * @return array the columns order where key it the column name and value the order direction ('asc' or 'desc')
     *
     * @psalm-return array<string, string>
     */
    private function getQueryOrders(DataTableQuery $query): array
    {
        $index = -1;
        $direction = DataColumn::SORT_ASC;
        if (!empty($query->order)) {
            $index = $query->order[0]->column - 1;
            $direction = $query->order[0]->dir;
        }

        switch ($index) {
            case 0: // entity
                return [
                    self::COLUMN_ENTITY => $direction,
                    self::COLUMN_CONTENT => self::SORT_ASC,
                    self::COLUMN_FIELD => self::SORT_ASC,
                ];
            case 1: // field
                return [
                    self::COLUMN_FIELD => $direction,
                    self::COLUMN_CONTENT => self::SORT_ASC,
                    self::COLUMN_ENTITY => self::SORT_ASC,
                 ];
            default: // content
                return [
                    self::COLUMN_CONTENT => $direction,
                    self::COLUMN_ENTITY => self::SORT_ASC,
                    self::COLUMN_FIELD => self::SORT_ASC,
                 ];
        }
    }

    /**
     * Update results.
     *
     * @param array $items the items to update
     * @psalm-param array<array{
     *      id: int,
     *      type: string,
     *      field: string,
     *      content: string,
     *      entityName: string,
     *      fieldName: string
     *  }> $items
     */
    private function processItems(array &$items): void
    {
        foreach ($items as &$item) {
            $type = $item[SearchService::COLUMN_TYPE];
            $field = $item[SearchService::COLUMN_FIELD];

            // translate entity and field names
            $lowerType = \strtolower($type);
            $item[self::COLUMN_ENTITY] = $this->trans("{$lowerType}.name");
            $item[self::COLUMN_FIELD] = $this->trans("{$lowerType}.fields.{$field}");

            // format content
            $content = $item[SearchService::COLUMN_CONTENT];
            switch ("{$type}.{$field}") {
                case 'Calculation.id':
                    $content = $this->formatId((int) $content);
                    break;
                case 'Calculation.overallTotal':
                case 'Product.price':
                    $content = \number_format((float) $content, 2, '.', '');
                    break;
            }
            $item[SearchService::COLUMN_CONTENT] = $content;

            // set authorizations
            $item[self::COLUMN_SHOW] = $this->isGrantedShow($type);
            $item[self::COLUMN_EDIT] = $this->isGrantedEdit($type);
            $item[self::COLUMN_DELETE] = $this->isGrantedDelete($type);
        }
    }

    /**
     * Sorts items.
     *
     * @param array $items  the items to sort
     * @param array $orders the order definitions where key is the field and value is the order ('asc' or 'desc')
     * @psalm-param array<array{
     *      id: int,
     *      type: string,
     *      field: string,
     *      content: string,
     *      entityName: string,
     *      fieldName: string
     *  }> $items
     *  @psalm-param array<string, string> $orders
     */
    private function sortItems(array &$items, array $orders): void
    {
        // convert orders
        $sorts = [];
        foreach ($orders as $column => $direction) {
            $sorts["[$column]"] = self::SORT_ASC === $direction;
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        \uasort($items, function (array $a, array $b) use ($sorts, $accessor) {
            foreach ($sorts as $field => $ascending) {
                $result = Utils::compare($a, $b, $field, $accessor, $ascending);
                if (0 !== $result) {
                    return $result;
                }
            }

            return 0;
        });
    }
}
