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
     * The action column name.
     */
    private const COLUMN_ACTION = 'action';

    /**
     * The content column name.
     */
    private const COLUMN_CONTENT = SearchService::COLUMN_CONTENT;

    /**
     * The entity column name.
     */
    private const COLUMN_ENTITY_NAME = 'entityName';

    /**
     * The entity column name.
     */
    private const COLUMN_FIELD_NAME = 'fieldName';

    /**
     * The delete granted column name.
     */
    private const COLUMN_GRANTED_DELETE = 'deleteGranted';

    /**
     * The edit granted column name.
     */
    private const COLUMN_GRANTED_EDIT = 'editGranted';

    /**
     * The show granted column name.
     */
    private const COLUMN_GRANTED_SHOW = 'showGranted';

    /**
     * The entity parameter name.
     */
    private const PARAM_ENTITY = 'entity';

    /**
     * The default sort columns order.
     */
    private const SORT_COLUMNS = [
        self::COLUMN_CONTENT,
        self::COLUMN_ENTITY_NAME,
        self::COLUMN_FIELD_NAME,
    ];

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
    public function getEntityClassName(): ?string
    {
        return null;
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
        if (0 !== $totalNotFiltered) {
            // process
            $this->processItems($items);

            // sort
            $this->sortItems($items, $sort, $order);

            // limit
            $items = \array_slice($items, $offset, $limit);

            // update entity name (icon)
            foreach ($items as &$item) {
                $this->updateItem($item);
            }
        } else {
            $items = [];
        }

        // ajax?
        if ($request->isXmlHttpRequest()) {
            return [
                self::PARAM_TOTAL_NOT_FILTERED => $totalNotFiltered,
                self::PARAM_TOTAL => $filtered,
                self::PARAM_ROWS => $items,
            ];
        }

        // page list
        $pageList = self::PAGE_LIST;
        $limit = \min($limit, \max($pageList));

        // card view
        $card = $this->getParamCard($request);

        // render
        $parameters = [
            // template parameters
            self::PARAM_COLUMNS => $this->getColumns(),
            self::PARAM_ROWS => $items,
            self::PARAM_PAGE_LIST => $pageList,
            self::PARAM_LIMIT => $limit,

            // custom parameters
            'entity' => $entity,
            'entities' => $this->service->getEntities(),

            // action parameters
            'params' => [
                self::PARAM_ID => $this->getParamId($request),
                self::PARAM_SEARCH => $search,
                self::PARAM_SORT => $sort,
                self::PARAM_ORDER => $order,
                self::PARAM_OFFSET => $offset,
                self::PARAM_LIMIT => $limit,
                self::PARAM_CARD => $card,
            ],

            // table attributes
            'attributes' => [
                'total-not-filtered' => $totalNotFiltered,
                'total-rows' => $filtered,

                'search' => \json_encode(true),
                'search-text' => $search,

                'page-list' => $this->implodePageList($pageList),
                'page-size' => $limit,
                'page-number' => $page,

                'card-view' => \json_encode($card),

                'sort-name' => $sort,
                'sort-order' => $order,
            ],
        ];

        return $this->updateParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return __DIR__ . '/Definition/search.json';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return [
            self::COLUMN_CONTENT => Column::SORT_ASC,
            self::COLUMN_ENTITY_NAME => Column::SORT_ASC,
            self::COLUMN_FIELD_NAME => Column::SORT_ASC,
        ];
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
            $entity = $this->trans("{$lowerType}.name");
            $item[self::COLUMN_ACTION] = $item['id'];
            $item[self::COLUMN_ENTITY_NAME] = $entity;
            $item[self::COLUMN_FIELD_NAME] = $this->trans("{$lowerType}.fields.{$field}");

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
            $item[self::COLUMN_GRANTED_SHOW] = $this->isGrantedShow($type);
            $item[self::COLUMN_GRANTED_EDIT] = $this->isGrantedEdit($type);
            $item[self::COLUMN_GRANTED_DELETE] = $this->isGrantedDelete($type);
        }
    }

    /**
     * Sorts items.
     *
     * @param array  $items the items to sort
     * @param string $sort  the sorted column
     * @param string $order the sorted direction ('asc' or 'desc')
     */
    private function sortItems(array &$items, string $sort, string $order): void
    {
        // create sort
        $sorts = ["[$sort]" => Column::SORT_ASC === $order];
        foreach (self::SORT_COLUMNS as $field) {
            $field = "[$field]";
            if (!\array_key_exists($field, $sorts)) {
                $sorts[$field] = true;
            }
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

    /**
     * Update the item.
     */
    private function updateItem(array &$item): void
    {
        $name = $item[self::COLUMN_ENTITY_NAME];
        $type = \strtolower($item[SearchService::COLUMN_TYPE]);

        $icon = 'file far';
        switch ($type) {
            case 'calculation':
                $icon = 'calculator fas';
                break;
            case 'calculationstate':
                $icon = 'flag far';
                break;
            case 'task':
                $icon = 'tasks fas';
                break;
            case 'category':
                $icon = 'folder far';
                break;
            case 'group':
                $icon = 'code-branch fas';
                break;
            case 'product':
                $icon = 'file-alt far';
                break;
            case 'customer':
                $icon = 'address-card far';
                break;
        }
        $item[self::COLUMN_ENTITY_NAME] = \sprintf('<i class="fa-fw fa-%s" aria-hidden="true"></i>&nbsp;%s', $icon, $name);
        $item[SearchService::COLUMN_TYPE] = $type;
        unset($item[SearchService::COLUMN_FIELD]);
    }
}
