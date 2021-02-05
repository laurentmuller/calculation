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

use App\Entity\Calculation;
use App\Interfaces\EntityVoterInterface;
use App\Service\SearchService;
use App\Traits\TranslatorTrait;
use App\Util\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The search table.
 *
 * @author Laurent Muller
 */
class SearchTable extends AbstractTable
{
    use TranslatorTrait;

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
     * The entity parameter name.
     */
    private const PARAM_ENTITY = 'entity';

    /**
     * @var AuthorizationCheckerInterface
     */
    private $checker;

    /**
     * @var bool
     */
    private $debug;

    /**
     * The granted values.
     *
     * @var bool[]
     */
    private $rights = [];

    /**
     * @var SearchService
     */
    private $service;

    /**
     * Constructor.
     */
    public function __construct(SearchService $service, AuthorizationCheckerInterface $checker, TranslatorInterface $translator, KernelInterface $kernel)
    {
        $this->service = $service;
        $this->checker = $checker;
        $this->translator = $translator;
        $this->debug = $kernel->isDebug();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClassName(): string
    {
        return Calculation::class;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(Request $request): array
    {
        // entity
        $entity = (string) $request->get(self::PARAM_ENTITY, '');

        // search
        $search = (string) $request->get(self::PARAM_SEARCH, '');
        if (\strlen($search) > 1) {
            $items = $this->service->search($search, $entity, SearchService::NO_LIMIT);
        } else {
            $items = [];
        }

        // total
        $totalNotFiltered = $filtered = \count($items);

        // limit parameters
        [$offset, $limit, $page] = $this->getLimit($request);

        //sort parameters
        $sort = (string) $this->getRequestValue($request, self::PARAM_SORT, self::COLUMN_CONTENT);
        $order = (string) $this->getRequestValue($request, self::PARAM_ORDER, Column::SORT_ASC);

        // found?
        if (!empty($items)) {
            // process
            $this->processItems($items);

            // sort
            $orders = $this->getSortOrder($sort, $order);
            $this->sortItems($items, $orders);

            // limit
            $rows = \array_slice($items, $offset, $limit);
        //$filtered = \count($rows);
        } else {
            $rows = [];
        }

        // ajax?
        if ($request->isXmlHttpRequest()) {
            return [
                'totalNotFiltered' => $totalNotFiltered,
                'total' => $filtered,
                'rows' => $rows,
            ];
        }

        // page list
        $pageList = self::PAGE_LIST;
        $limit = \min($limit, \max($pageList));

        // render
        return [
            'columns' => $this->getColumns(),
            'rows' => $rows,

            'entity' => $entity,
            'entities' => $this->service->getEntities(),

            'id' => $this->getParamId($request),
            'card' => $this->getParamCard($request),

            'totalNotFiltered' => $totalNotFiltered,
            'total' => $filtered,

            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'search' => $search,
            'pageList' => $pageList,

            'sort' => $sort,
            'order' => $order,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return __DIR__ . '/Definition/search.json';
    }

    /**
     * Gets the limit, the maximum and the page parameters.
     *
     * @param Request $request the request to get values from
     *
     * @return int[] the offset, the limit and the page parameters
     */
    private function getLimit(Request $request): array
    {
        $offset = (int) $request->get(self::PARAM_OFFSET, 0);
        $limit = (int) $this->getRequestValue($request, self::PARAM_LIMIT, self::PAGE_SIZE);
        $page = 1 + (int) \floor($this->safeDivide($offset, $limit));

        return [$offset, $limit, $page];
    }

    /**
     * Gets the columns order for the given request.
     *
     * @param string $sort  the sorted column
     * @param string $order the sorted direction
     *
     * @return array the columns order where key it the column name and value the order direction ('asc' or 'desc')
     */
    private function getSortOrder(string $sort, string $order): array
    {
        switch ($sort) {
            case self::COLUMN_ENTITY:
                return [
                    self::COLUMN_ENTITY => $order,
                    self::COLUMN_CONTENT => Column::SORT_ASC,
                    self::COLUMN_FIELD => Column::SORT_ASC,
                ];
            case self::COLUMN_FIELD:
                return [
                    self::COLUMN_FIELD => $order,
                    self::COLUMN_CONTENT => Column::SORT_ASC,
                    self::COLUMN_ENTITY => Column::SORT_ASC,
                ];
            case self::COLUMN_CONTENT:
            default:
                return [
                    self::COLUMN_CONTENT => $order,
                    self::COLUMN_ENTITY => Column::SORT_ASC,
                    self::COLUMN_FIELD => Column::SORT_ASC,
                ];
        }
    }

    /**
     * Returns if the given action for the given subject is granted.
     *
     * @param string $action  the action to be tested
     * @param string $subject the subject (the entity name)
     *
     * @return bool true if the action is granted
     */
    private function isGranted(string $action, string $subject): bool
    {
        $key = "{$action}.{$subject}";
        if (!isset($this->rights[$key])) {
            return $this->rights[$key] = $this->checker->isGranted($action, $subject);
        }

        return $this->rights[$key];
    }

    /**
     * Returns if the given subject can be deleted.
     *
     * @param string $subject the subject (entity name)
     *
     * @return bool true if the subject can be deleted
     */
    private function isGrantedDelete(string $subject): bool
    {
        return $this->isGranted(EntityVoterInterface::ATTRIBUTE_DELETE, $subject);
    }

    /**
     * Returns if the given subject can be edited.
     *
     * @param string $subject the subject (entity name)
     *
     * @return bool true if the subject can be edited
     */
    private function isGrantedEdit(string $subject): bool
    {
        return $this->isGranted(EntityVoterInterface::ATTRIBUTE_EDIT, $subject);
    }

    /**
     * Returns if the given subject can be displayed.
     *
     * @param string $subject the subject (entity name)
     *
     * @return bool true if the subject can be displayed
     */
    private function isGrantedShow(string $subject): bool
    {
        return $this->isGranted(EntityVoterInterface::ATTRIBUTE_SHOW, $subject);
    }

    /**
     * Update results.
     *
     * @param array $items the items to update
     */
    private function processItems(array &$items): void
    {
        foreach ($items as &$item) {
            $type = $item[SearchService::COLUMN_TYPE];
            $field = $item[SearchService::COLUMN_FIELD];

            // translate entity and field names
            $lowerType = \strtolower($type);
            $item[Column::COL_ACTION] = $item['id'];
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
                    $content = $this->formatAmount((float) $content);
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
     */
    private function sortItems(array &$items, array $orders): void
    {
        // convert orders
        $sorts = [];
        foreach ($orders as $column => $direction) {
            $sorts["[$column]"] = Column::SORT_ASC === $direction;
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
